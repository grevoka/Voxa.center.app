<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InstallController extends Controller
{
    /**
     * Show the install wizard.
     */
    public function index()
    {
        if (self::isInstalled()) {
            return redirect('/');
        }

        $step = session('install_step', 1);
        $requirements = $this->checkRequirements();

        return view('install.wizard', compact('step', 'requirements'));
    }

    /**
     * Step 1: Check requirements then advance.
     */
    public function requirements(Request $request)
    {
        $reqs = $this->checkRequirements();
        if (in_array(false, $reqs, true)) {
            return back()->with('error', 'Certaines conditions ne sont pas remplies.');
        }

        session(['install_step' => 2]);
        return redirect()->route('install.index');
    }

    /**
     * Step 2: Test & save database configuration.
     */
    public function database(Request $request)
    {
        $data = $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            // Asterisk Realtime DB
            'db_ast_host'     => 'required|string',
            'db_ast_port'     => 'required|integer',
            'db_ast_database' => 'required|string',
            'db_ast_username' => 'required|string',
            'db_ast_password' => 'nullable|string',
        ]);

        // Test main DB connection
        try {
            $pdo = new \PDO(
                "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']}",
                $data['db_username'],
                $data['db_password'] ?? '',
                [\PDO::ATTR_TIMEOUT => 5]
            );
            $pdo->query('SELECT 1');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Connexion DB principale echouee : ' . $e->getMessage());
        }

        // Test Asterisk DB connection
        try {
            $pdo2 = new \PDO(
                "mysql:host={$data['db_ast_host']};port={$data['db_ast_port']};dbname={$data['db_ast_database']}",
                $data['db_ast_username'],
                $data['db_ast_password'] ?? '',
                [\PDO::ATTR_TIMEOUT => 5]
            );
            $pdo2->query('SELECT 1');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Connexion DB Asterisk echouee : ' . $e->getMessage());
        }

        // Write .env
        $this->updateEnv([
            'APP_ENV'          => 'production',
            'APP_DEBUG'        => 'false',
            'APP_URL'          => $request->input('app_url', config('app.url')),
            'DB_CONNECTION'    => 'mysql',
            'DB_HOST'          => $data['db_host'],
            'DB_PORT'          => $data['db_port'],
            'DB_DATABASE'      => $data['db_database'],
            'DB_USERNAME'      => $data['db_username'],
            'DB_PASSWORD'      => $data['db_password'] ?? '',
            'DB_AST_HOST'      => $data['db_ast_host'],
            'DB_AST_PORT'      => $data['db_ast_port'],
            'DB_AST_DATABASE'  => $data['db_ast_database'],
            'DB_AST_USERNAME'  => $data['db_ast_username'],
            'DB_AST_PASSWORD'  => $data['db_ast_password'] ?? '',
        ]);

        // Generate app key if missing
        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // Reload config with new values
        Artisan::call('config:clear');

        // Reconfigure DB connection at runtime
        config([
            'database.connections.mysql.host'     => $data['db_host'],
            'database.connections.mysql.port'     => $data['db_port'],
            'database.connections.mysql.database' => $data['db_database'],
            'database.connections.mysql.username'  => $data['db_username'],
            'database.connections.mysql.password'  => $data['db_password'] ?? '',
        ]);
        DB::purge('mysql');

        // Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Migration echouee : ' . $e->getMessage());
        }

        session(['install_step' => 3, 'install_db' => $data]);
        return redirect()->route('install.index');
    }

    /**
     * Step 3: Create admin account.
     */
    public function admin(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Ensure DB is connected
        $dbConf = session('install_db');
        if ($dbConf) {
            config([
                'database.connections.mysql.host'     => $dbConf['db_host'],
                'database.connections.mysql.port'     => $dbConf['db_port'],
                'database.connections.mysql.database' => $dbConf['db_database'],
                'database.connections.mysql.username'  => $dbConf['db_username'],
                'database.connections.mysql.password'  => $dbConf['db_password'] ?? '',
            ]);
            DB::purge('mysql');
        }

        try {
            $userModel = config('auth.providers.users.model', \App\Models\User::class);
            $userModel::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ],
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Creation admin echouee : ' . $e->getMessage());
        }

        session(['install_step' => 4]);
        return redirect()->route('install.index');
    }

    /**
     * Step 4: Finalize — lock installer.
     */
    public function finalize(Request $request)
    {
        // Seed default contexts
        try {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CallContextSeeder', '--force' => true]);
        } catch (\Throwable) {
            // Non-blocking — user can create contexts manually
        }

        // Create lock file
        file_put_contents(storage_path('installed'), now()->toDateTimeString());

        // Clear all caches
        Artisan::call('optimize:clear');

        session()->flush();

        return redirect('/')->with('success', 'Installation terminee ! Connectez-vous avec votre compte admin.');
    }

    /**
     * Check if the app is already installed.
     */
    public static function isInstalled(): bool
    {
        return file_exists(storage_path('installed'));
    }

    /**
     * Check system requirements.
     */
    private function checkRequirements(): array
    {
        return [
            'PHP >= 8.3'          => version_compare(PHP_VERSION, '8.3.0', '>='),
            'PDO MySQL'           => extension_loaded('pdo_mysql'),
            'Mbstring'            => extension_loaded('mbstring'),
            'OpenSSL'             => extension_loaded('openssl'),
            'Tokenizer'           => extension_loaded('tokenizer'),
            'XML'                 => extension_loaded('xml'),
            'Ctype'               => extension_loaded('ctype'),
            'JSON'                => extension_loaded('json'),
            'BCMath'              => extension_loaded('bcmath'),
            'Zip'                 => extension_loaded('zip'),
            'storage/ writable'   => is_writable(storage_path()),
            'bootstrap/cache/ writable' => is_writable(base_path('bootstrap/cache')),
            '.env writable'       => is_writable(base_path('.env')) || is_writable(base_path()),
        ];
    }

    /**
     * Update .env file values.
     */
    private function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            copy(base_path('.env.example'), $envPath);
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            // Wrap in quotes if contains spaces
            $escaped = Str::contains($value, ' ') ? "\"{$value}\"" : $value;

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $content);
            } else {
                $content .= "\n{$key}={$escaped}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
