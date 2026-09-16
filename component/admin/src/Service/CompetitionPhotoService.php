<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

final class CompetitionPhotoService
{
    /**
     * Resolve the effective portrait without copying files between products.
     * Priority: roster/edition photo -> People profile document -> legacy player photo.
     */
    public function resolve(?string $rosterPhoto, ?array $person, ?string $legacyPlayerPhoto): array
    {
        $rosterPhoto = trim((string) $rosterPhoto);
        if ($rosterPhoto !== '') {
            return [
                'source' => 'roster',
                'media' => $rosterPhoto,
                'profile_document_uuid' => null,
                'profile_document_reference' => null,
            ];
        }

        $profileDocumentReference = is_array($person['profile_document_reference'] ?? null)
            ? $person['profile_document_reference']
            : null;
        $profileDocumentUuid = trim((string) ($person['profile_document_uuid'] ?? ''));
        if ($profileDocumentReference !== null || $profileDocumentUuid !== '') {
            return [
                'source' => 'people',
                'media' => null,
                'profile_document_uuid' => $profileDocumentUuid !== '' ? $profileDocumentUuid : null,
                'profile_document_reference' => $profileDocumentReference,
            ];
        }

        $legacyPlayerPhoto = trim((string) $legacyPlayerPhoto);
        if ($legacyPlayerPhoto !== '') {
            return [
                'source' => 'legacy',
                'media' => $legacyPlayerPhoto,
                'profile_document_uuid' => null,
                'profile_document_reference' => null,
            ];
        }

        return [
            'source' => 'none',
            'media' => null,
            'profile_document_uuid' => null,
            'profile_document_reference' => null,
        ];
    }
}
