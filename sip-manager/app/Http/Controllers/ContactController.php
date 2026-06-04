<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $query = Contact::query();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('prenom', 'like', "%{$q}%")
                  ->orWhere('nom', 'like', "%{$q}%")
                  ->orWhere('telephone', 'like', "%{$q}%")
                  ->orWhere('phone_normalized', 'like', "%{$q}%");
            });
        }
        $contacts = $query->orderBy('nom')->orderBy('prenom')->paginate(50)->withQueryString();
        return view('contacts.index', compact('contacts', 'q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prenom'    => 'required|string|max:100',
            'nom'       => 'required|string|max:100',
            'telephone' => 'required|string|max:40',
        ]);
        $data['prenom']    = trim($data['prenom']);
        $data['nom']       = trim($data['nom']);
        $data['telephone'] = trim($data['telephone']);
        $data['phone_normalized'] = Contact::normalizePhone($data['telephone']);
        $data['created_by'] = auth()->id();

        $exists = Contact::where('prenom', $data['prenom'])
            ->where('nom', $data['nom'])
            ->where('phone_normalized', $data['phone_normalized'])
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->with('warning', "Doublon: {$data['prenom']} {$data['nom']} ({$data['telephone']}) existe deja.");
        }

        Contact::create($data);
        return redirect()->route('contacts.index')
            ->with('success', "Contact {$data['prenom']} {$data['nom']} ajoute.");
    }

    public function destroy(Contact $contact)
    {
        $name = "{$contact->prenom} {$contact->nom}";
        $contact->delete();
        return back()->with('success', "Contact {$name} supprime.");
    }

    public function import(Request $request)
    {
        return view('contacts.import');
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel|max:5120',
        ]);

        $path = $request->file('csv')->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Impossible de lire le fichier.');
        }

        // Detect separator: a header line with ; or , — fall back to comma.
        $first = fgets($handle);
        $sep = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';
        rewind($handle);

        $imported   = 0;
        $duplicates = [];
        $errors     = [];
        $userId     = auth()->id();
        $rowNo      = 0;
        $skippedHeader = false;

        while (($row = fgetcsv($handle, 0, $sep)) !== false) {
            $rowNo++;
            // Empty / whitespace rows
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) continue;

            $prenom    = trim($row[0] ?? '');
            $nom       = trim($row[1] ?? '');
            $telephone = trim($row[2] ?? '');

            // First non-empty row that looks like a header label set
            if (!$skippedHeader && in_array(strtolower($prenom), ['prenom', 'prénom', 'firstname', 'first_name'], true)) {
                $skippedHeader = true;
                continue;
            }
            $skippedHeader = true;

            if ($prenom === '' || $nom === '' || $telephone === '') {
                $errors[] = "Ligne {$rowNo}: champ manquant (prenom/nom/telephone)";
                continue;
            }
            if (strlen($prenom) > 100 || strlen($nom) > 100 || strlen($telephone) > 40) {
                $errors[] = "Ligne {$rowNo}: champ trop long";
                continue;
            }

            $norm = Contact::normalizePhone($telephone);

            $exists = Contact::where('prenom', $prenom)
                ->where('nom', $nom)
                ->where('phone_normalized', $norm)
                ->first();

            if ($exists) {
                $duplicates[] = "{$prenom} {$nom} — {$telephone}";
                continue;
            }

            Contact::create([
                'prenom'           => $prenom,
                'nom'              => $nom,
                'telephone'        => $telephone,
                'phone_normalized' => $norm,
                'created_by'       => $userId,
            ]);
            $imported++;
        }
        fclose($handle);

        return view('contacts.import', [
            'imported'   => $imported,
            'duplicates' => $duplicates,
            'errors'     => $errors,
        ]);
    }
}
