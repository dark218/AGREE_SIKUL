<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ValidationService
{
    /**
     * Valider le KYC d'un utilisateur
     *
     * @param Model $entity L'entité à valider
     * @return array ['success' => bool, 'message' => string]
     */
    public static function validateKyc(Model $entity): array
    {
        $entity->update([
            'kyc_status' => config('appconstants.user_kyc_status.verifie'),
            'statut' => config('appconstants.user_statut.actif'),
            'validated_at' => Carbon::now(),
            'validated_by' => Auth::id(),
        ]);

        SMSApiProService::sendNewSms(
            $entity->full_login,
            "Bonjour {$entity->nom} {$entity->prenoms}, votre KYC a été validé. Votre compte est maintenant vérifié et actif."
        );

        return [
            'success' => true,
            'message' => trans('kycvalidationsucces'),
        ];
    }

    /**
     * Rejeter le KYC d'un utilisateur
     *
     * @param Model $entity L'entité à rejeter
     * @param string $motif Le motif du rejet
     * @return array ['success' => bool, 'message' => string]
     */
    public static function rejectKyc(Model $entity, string $motif): array
    {
        if (empty($motif)) {
            return [
                'success' => false,
                'message' => trans('motifrequired'),
            ];
        }

        $entity->update([
            'kyc_status' => config('appconstants.user_kyc_status.rejete'),
            'statut' => config('appconstants.user_statut.suspendu'),
            'validated_at' => Carbon::now(),
            'validated_by' => Auth::id(),
            'motif' => $motif,
        ]);

        SMSApiProService::sendNewSms(
            $entity->full_login,
            "Bonjour {$entity->nom} {$entity->prenoms}, votre KYC a été rejeté pour motif: {$motif}. Veuillez mettre à jour vos documents."
        );

        return [
            'success' => true,
            'message' => trans('kycrejetsucces'),
        ];
    }

    /**
     * Valider le statut d'une entité (Marchand, Employe, etc.) et son utilisateur associé
     *
     * @param Model $entity L'entité à valider (Marchand, Employe, etc.)
     * @param User|null $user L'utilisateur associé (optionnel, pour PointVente par exemple)
     * @param bool $alsoValidateKyc Valider aussi le KYC de l'utilisateur
     * @return array ['success' => bool, 'message' => string]
     */
    public static function validateStatut(Model $entity, ?User $user = null, bool $alsoValidateKyc = true): array
    {
        // Mettre à jour le statut de l'entité
        if (Schema::hasColumn($entity->getTable(), 'statut')) {
            $entity->update([
                'statut' => config('appconstants.user_statut.actif'),
                'validated_at' => Carbon::now(),
                'validated_by' => Auth::id(),
            ]);
        }

        // Si un utilisateur est associé, mettre à jour son statut aussi
        if ($user) {
            $updateData = [
                'statut' => config('appconstants.user_statut.actif'),
                'validated_at' => Carbon::now(),
                'validated_by' => Auth::id(),
            ];

            // Valider aussi le KYC si demandé
            if ($alsoValidateKyc) {
                $updateData['kyc_status'] = config('appconstants.user_kyc_status.verifie');
            }

            $user->update($updateData);

            // Envoyer SMS de confirmation
            $entityName = self::getEntityName($entity);
            SMSApiProService::sendNewSms(
                $user->full_login,
                "Bonjour {$user->nom} {$user->prenoms}, votre compte {$entityName} a été validé. Vous pouvez désormais effectuer vos opérations sur l'application Agree Sikul."
            );
        }

        return [
            'success' => true,
            'message' => trans('validationsucces'),
        ];
    }

    /**
     * Rejeter le statut d'une entité (Marchand, Employe, etc.) et son utilisateur associé
     *
     * @param Model $entity L'entité à rejeter
     * @param string $motif Le motif du rejet
     * @param User|null $user L'utilisateur associé (optionnel)
     * @param bool $alsoRejectKyc Rejeter aussi le KYC de l'utilisateur
     * @return array ['success' => bool, 'message' => string]
     */
    public static function rejectStatut(Model $entity, string $motif, ?User $user = null, bool $alsoRejectKyc = true): array
    {
        if (empty($motif)) {
            return [
                'success' => false,
                'message' => trans('motifrequired'),
            ];
        }

        // Mettre à jour le statut de l'entité
        if (Schema::hasColumn($entity->getTable(), 'statut')) {
            $entity->update([
                'statut' => config('appconstants.user_statut.suspendu'),
                'motif' => $motif,
                'validated_at' => Carbon::now(),
                'validated_by' => Auth::id(),
            ]);
        }

        // Si un utilisateur est associé, mettre à jour son statut aussi
        if ($user) {
            $updateData = [
                'statut' => config('appconstants.user_statut.suspendu'),
                'validated_at' => Carbon::now(),
                'validated_by' => Auth::id(),
                'motif' => $motif,
            ];

            // Rejeter aussi le KYC si demandé
            if ($alsoRejectKyc) {
                $updateData['kyc_status'] = config('appconstants.user_kyc_status.rejete');
            }

            $user->update($updateData);

            // Envoyer SMS de notification
            $entityName = self::getEntityName($entity);
            SMSApiProService::sendNewSms(
                $user->full_login,
                "Bonjour {$user->nom} {$user->prenoms}, votre compte {$entityName} a été rejeté pour motif: {$motif}. Veuillez contacter le support."
            );
        }

        return [
            'success' => true,
            'message' => trans('rejetsucces'),
        ];
    }

    /**
     * Valider uniquement le statut d'une entité sans utilisateur (ex: PointVente)
     *
     * @param Model $entity L'entité à valider
     * @return array ['success' => bool, 'message' => string]
     */
    public static function validateEntityStatut(Model $entity): array
    {
        $entity->update([
            'statut' => config('appconstants.user_statut.actif'),
            'validated_at' => Carbon::now(),
            'validated_by' => Auth::id(),
        ]);

        return [
            'success' => true,
            'message' => trans('validationsucces'),
        ];
    }

    /**
     * Rejeter uniquement le statut d'une entité sans utilisateur (ex: PointVente)
     *
     * @param Model $entity L'entité à rejeter
     * @param string $motif Le motif du rejet
     * @return array ['success' => bool, 'message' => string]
     */
    public static function rejectEntityStatut(Model $entity, string $motif): array
    {
        if (empty($motif)) {
            return [
                'success' => false,
                'message' => trans('motifrequired'),
            ];
        }

        $entity->update([
            'statut' => config('appconstants.user_statut.suspendu'),
            'motif' => $motif,
            'validated_at' => Carbon::now(),
            'validated_by' => Auth::id(),
        ]);

        return [
            'success' => true,
            'message' => trans('rejetsucces'),
        ];
    }

    /**
     * Suspendre un utilisateur
     *
     * @param User $user L'utilisateur à suspendre
     * @param string $motif Le motif de la suspension
     * @return array ['success' => bool, 'message' => string]
     */
    public static function suspendUser(User $user, string $motif): array
    {
        if (empty($motif)) {
            return [
                'success' => false,
                'message' => trans('motifrequired'),
            ];
        }

        $user->update([
            'statut' => config('appconstants.user_statut.suspendu'),
            'motif' => $motif,
            'validated_at' => null,
            'validated_by' => null,
            'suspended_by' => Auth::id(),
            'blocked_by' => null,
        ]);

        SMSApiProService::sendNewSms(
            $user->full_login,
            "Bonjour {$user->nom} {$user->prenoms}, votre compte a été suspendu pour motif: {$motif}. Veuillez contacter le support."
        );

        return [
            'success' => true,
            'message' => trans('suspensionsucces'),
        ];
    }

    /**
     * Bloquer un utilisateur
     *
     * @param User $user L'utilisateur à bloquer
     * @param string $motif Le motif du blocage
     * @return array ['success' => bool, 'message' => string]
     */
    public static function blockUser(User $user, string $motif): array
    {
        if (empty($motif)) {
            return [
                'success' => false,
                'message' => trans('motifrequired'),
            ];
        }

        $user->update([
            'statut' => config('appconstants.user_statut.bloque'),
            'motif' => $motif,
            'validated_at' => null,
            'validated_by' => null,
            'blocked_by' => Auth::id(),
            'suspended_by' => null,
        ]);

        SMSApiProService::sendNewSms(
            $user->full_login,
            "Bonjour {$user->nom} {$user->prenoms}, votre compte a été bloqué pour motif: {$motif}. Veuillez contacter le support."
        );

        return [
            'success' => true,
            'message' => trans('blocagesucces'),
        ];
    }

    /**
     * Mettre le KYC en attente
     *
     * @param User $user L'utilisateur
     * @return array ['success' => bool, 'message' => string]
     */
    public static function setKycEnAttente(User $user): array
    {
        if ($user->kyc_status === config('appconstants.user_kyc_status.non_verifie')) {
            $user->update([
                'kyc_status' => config('appconstants.user_kyc_status.en_attente'),
            ]);
        }

        return [
            'success' => true,
            'message' => 'OK',
        ];
    }

    /**
     * Obtenir le nom de l'entité pour les messages
     *
     * @param Model $entity
     * @return string
     */
    private static function getEntityName(Model $entity): string
    {
        $className = class_basename($entity);

        $names = [
            'Marchand' => 'marchand',
            'Employe' => 'employé',
            'PointVente' => 'point de vente',
            'Client' => 'client',
            'Agent' => 'agent',
        ];

        return $names[$className] ?? strtolower($className);
    }
}
