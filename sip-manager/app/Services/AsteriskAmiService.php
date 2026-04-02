<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AsteriskAmiService
{
    /**
     * Recharger le module PJSIP
     */
    public function pjsipReload(): bool
    {
        // AMI integration - requires PAMI package
        // For now, return true as placeholder
        Log::info('AMI: PJSIP reload requested');
        return true;
    }

    /**
     * Recuperer le statut d'un endpoint
     */
    public function getEndpointStatus(string $endpointId): array
    {
        return ['available' => false, 'raw' => ''];
    }

    /**
     * Lister les appels actifs
     */
    public function getActiveCalls(): array
    {
        return [];
    }

    /**
     * Recuperer le statut de registration d'un trunk
     */
    public function getTrunkRegistrationStatus(string $trunkId): string
    {
        return 'offline';
    }

    /**
     * Executer une commande CLI quelconque
     */
    public function command(string $command): string
    {
        return '';
    }
}
