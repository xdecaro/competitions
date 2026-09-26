from pathlib import Path

path = Path('component/admin/src/Service/PublicBuilderDataService.php')
text = path.read_text(encoding='utf-8')

if 'public function getRoster(int $seasonId, int $teamId, bool $approvedOnly = true): array' in text:
    raise SystemExit('Public entity methods already present; nothing to apply.')

marker = '    private function getCompetitionRows(\n'
if marker not in text:
    raise SystemExit('Insertion marker not found.')

methods = r'''    public function getFederation(int $federationId): ?array
    {
        if ($federationId < 1) {
            return null;
        }

        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('f.id', 'id'),
                $db->quoteName('f.country_id', 'country_id'),
                $db->quoteName('c.name', 'country_name'),
                $db->quoteName('c.code', 'country_code'),
                $db->quoteName('f.name', 'name'),
                $db->quoteName('f.short_name', 'short_name'),
                $db->quoteName('f.logo', 'logo'),
                $db->quoteName('f.website', 'website'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_federations', 'f'))
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_countries', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('f.country_id')
                . ' AND ' . $db->quoteName('c.state') . ' = 1'
            )
            ->where($db->quoteName('f.id') . ' = :federationId')
            ->where($db->quoteName('f.state') . ' = 1')
            ->bind(':federationId', $federationId, ParameterType::INTEGER);

        $row = $db->setQuery($query, 0, 1)->loadAssoc();

        if (!$row) {
            return null;
        }

        $row['id'] = (int) ($row['id'] ?? 0);
        $row['country_id'] = (int) ($row['country_id'] ?? 0);

        return $row;
    }

    public function getTeams(?int $federationId = null, int $limit = 250): array
    {
        if ($federationId !== null && $federationId < 1) {
            return [];
        }

        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('tm.id', 'id'),
                $db->quoteName('tm.name', 'name'),
                $db->quoteName('tm.short_name', 'short_name'),
                $db->quoteName('tm.alias', 'alias'),
                $db->quoteName('tm.logo', 'logo'),
                $db->quoteName('tm.country_code', 'country_code'),
                $db->quoteName('tm.city', 'city'),
                $db->quoteName('tm.federation_id', 'federation_id'),
                $db->quoteName('f.name', 'federation_name'),
                $db->quoteName('f.short_name', 'federation_short_name'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_teams', 'tm'))
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_federations', 'f')
                . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('tm.federation_id')
                . ' AND ' . $db->quoteName('f.state') . ' = 1'
            )
            ->where($db->quoteName('tm.state') . ' = 1')
            ->where($db->quoteName('tm.approval_status') . ' = ' . $db->quote('approved'))
            ->order($db->quoteName('tm.name') . ' ASC');

        if ($federationId !== null) {
            $query->where($db->quoteName('tm.federation_id') . ' = :teamsFederationId')
                ->bind(':teamsFederationId', $federationId, ParameterType::INTEGER);
        }

        $rows = $db->setQuery($query, 0, $this->clampLimit($limit, 250))->loadAssocList() ?: [];

        return array_map([$this, 'normalizeTeam'], $rows);
    }

    public function getFederationTeams(int $federationId, int $limit = 250): array
    {
        if ($federationId < 1) {
            return [];
        }

        return $this->getTeams($federationId, $limit);
    }

    public function getRoster(int $seasonId, int $teamId, bool $approvedOnly = true): array
    {
        if ($seasonId < 1 || $teamId < 1) {
            return [];
        }

        $db = $this->db;
        $query = $this->createPublicRosterQuery()
            ->where($db->quoteName('p.season_id') . ' = :rosterSeasonId')
            ->where($db->quoteName('tm.id') . ' = :rosterTeamId')
            ->bind(':rosterSeasonId', $seasonId, ParameterType::INTEGER)
            ->bind(':rosterTeamId', $teamId, ParameterType::INTEGER)
            ->order($db->quoteName('r.ordering') . ' ASC')
            ->order($db->quoteName('r.shirt_number') . ' ASC')
            ->order($db->quoteName('pl.last_name') . ' ASC')
            ->order($db->quoteName('pl.first_name') . ' ASC');

        if ($approvedOnly) {
            $query->where($db->quoteName('p.status') . ' = ' . $db->quote('approved'))
                ->where($db->quoteName('r.status') . ' = ' . $db->quote('approved'));
        }

        $rows = $db->setQuery($query)->loadAssocList() ?: [];

        return array_map([$this, 'normalizeRosterRow'], $rows);
    }

    public function getRosterPlayer(int $rosterId): ?array
    {
        if ($rosterId < 1) {
            return null;
        }

        $db = $this->db;
        $query = $this->createPublicRosterQuery()
            ->where($db->quoteName('r.id') . ' = :publicRosterId')
            ->where($db->quoteName('p.status') . ' = ' . $db->quote('approved'))
            ->where($db->quoteName('r.status') . ' = ' . $db->quote('approved'))
            ->bind(':publicRosterId', $rosterId, ParameterType::INTEGER);

        $row = $db->setQuery($query, 0, 1)->loadAssoc();

        return $row ? $this->normalizeRosterRow($row) : null;
    }

    private function createPublicRosterQuery()
    {
        $db = $this->db;

        return $db->getQuery(true)
            ->select([
                $db->quoteName('r.id', 'roster_id'),
                $db->quoteName('pl.id', 'player_id'),
                $db->quoteName('pl.first_name', 'first_name'),
                $db->quoteName('pl.last_name', 'last_name'),
                $db->quoteName('pl.nationality_code', 'nationality_code'),
                $db->quoteName('r.shirt_number', 'shirt_number'),
                $db->quoteName('r.role', 'role'),
                $db->quoteName('pl.photo', 'photo'),
                $db->quoteName('tm.id', 'team_id'),
                $db->quoteName('tm.name', 'team_name'),
                $db->quoteName('p.season_id', 'season_id'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_rosters', 'r'))
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_participations', 'p')
                . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.participation_id')
            )
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_players', 'pl')
                . ' ON ' . $db->quoteName('pl.id') . ' = ' . $db->quoteName('r.player_id')
            )
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_teams', 'tm')
                . ' ON ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('p.team_id')
                . ' AND ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('r.team_id')
            )
            ->where($db->quoteName('r.state') . ' = 1')
            ->where($db->quoteName('p.state') . ' = 1')
            ->where($db->quoteName('pl.state') . ' = 1')
            ->where($db->quoteName('tm.state') . ' = 1')
            ->where($db->quoteName('pl.approval_status') . ' = ' . $db->quote('approved'))
            ->where($db->quoteName('tm.approval_status') . ' = ' . $db->quote('approved'));
    }

    private function normalizeRosterRow(array $row): array
    {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));

        return [
            'roster_id' => (int) ($row['roster_id'] ?? 0),
            'player_id' => (int) ($row['player_id'] ?? 0),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => trim($firstName . ' ' . $lastName),
            'nationality_code' => $this->nullableString($row['nationality_code'] ?? null),
            'shirt_number' => isset($row['shirt_number']) && $row['shirt_number'] !== null ? (int) $row['shirt_number'] : null,
            'role' => $this->nullableString($row['role'] ?? null),
            'photo' => $this->nullableString($row['photo'] ?? null),
            'team_id' => (int) ($row['team_id'] ?? 0),
            'team_name' => (string) ($row['team_name'] ?? ''),
            'season_id' => (int) ($row['season_id'] ?? 0),
        ];
    }

'''

path.write_text(text.replace(marker, methods + marker, 1), encoding='utf-8')
print('Applied public entity methods to PublicBuilderDataService.php')
