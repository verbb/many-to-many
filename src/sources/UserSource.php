<?php
namespace verbb\manytomany\sources;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\Gql;

class UserSource extends AbstractRelationSource
{
    public function elementType(): string
    {
        return User::class;
    }

    public function label(): string
    {
        return Craft::t('app', 'Users');
    }

    public function sourceType(): string
    {
        return 'userGroup';
    }

    public function sourceLabel(): string
    {
        return Craft::t('manytomany', 'Linked User Group');
    }

    public function sourceInstructions(): string
    {
        return Craft::t('manytomany', 'Choose the user group containing the users that own the associated field.');
    }

    public function sourceOptions(): array
    {
        $options = [
            '*' => Craft::t('app', 'All users'),
        ];

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
            $options[$group->uid] = $group->name;
        }

        return $options;
    }

    public function sourceExists(string $sourceValue): bool
    {
        return $sourceValue === '*' || Craft::$app->getUserGroups()->getGroupByUid($sourceValue) !== null;
    }

    public function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add a user');
    }

    public function selectorSources(string $sourceValue): array|string
    {
        return $sourceValue === '*' ? '*' : ["group:$sourceValue"];
    }

    public function getEagerLoadingGqlConditions(string $sourceValue): ?array
    {
        $allowedGroupUids = Gql::extractAllowedEntitiesFromSchema()['usergroups'] ?? [];

        if ($sourceValue === '*') {
            if (in_array('everyone', $allowedGroupUids, true) || in_array('solo', $allowedGroupUids, true)) {
                return [];
            }

            $groupIds = array_filter(array_map(
                fn(string $uid) => Craft::$app->getUserGroups()->getGroupByUid($uid)?->id,
                $allowedGroupUids,
            ));

            return empty($groupIds) ? null : ['groupId' => $groupIds];
        }

        if (!in_array($sourceValue, $allowedGroupUids, true)) {
            return null;
        }

        $group = Craft::$app->getUserGroups()->getGroupByUid($sourceValue);

        return $group ? ['groupId' => [$group->id]] : null;
    }

    public function gqlInterface(): string
    {
        return UserInterface::class;
    }

    public function gqlArguments(): string
    {
        return UserArguments::class;
    }

    public function gqlResolver(): string
    {
        return UserResolver::class;
    }

    public function applySource(ElementQueryInterface $query, string $sourceValue): void
    {
        if ($sourceValue !== '*') {
            $query->groupId(Craft::$app->getUserGroups()->getGroupByUid($sourceValue)?->id ?? 0);
        }
    }

    protected function _sourceCriteria(string $sourceValue): array
    {
        return $sourceValue === '*'
            ? []
            : ['groupId' => Craft::$app->getUserGroups()->getGroupByUid($sourceValue)?->id ?? 0];
    }

    protected function _applyExistsSource(Query $query, string $sourceValue, string $namespace): void
    {
        $elementsAlias = "elements_$namespace";
        $usersAlias = "users_$namespace";
        $query
            ->innerJoin([$usersAlias => Table::USERS], "[[$usersAlias.id]] = [[$elementsAlias.id]]")
            ->andWhere([
                "$usersAlias.active" => true,
                "$usersAlias.suspended" => false,
            ]);

        if ($sourceValue !== '*') {
            $userGroupsAlias = "usergroups_users_$namespace";
            $groupId = Craft::$app->getUserGroups()->getGroupByUid($sourceValue)?->id ?? 0;
            $query
                ->innerJoin([$userGroupsAlias => Table::USERGROUPS_USERS], "[[$userGroupsAlias.userId]] = [[$elementsAlias.id]]")
                ->andWhere(["$userGroupsAlias.groupId" => $groupId]);
        }
    }
}
