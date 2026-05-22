<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Tool\TmpStore;

use Exception;
use Pimcore\Db\Helper;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Tool\TmpStore $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function add(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null): bool
    {
        try {
            $serialized = false;
            if (is_object($data) || is_array($data)) {
                $serialized = true;
                $data = serialize($data);
            }

            Helper::upsert($this->db, 'tmp_store', [
                'id' => $id,
                'data' => $data,
                'tag' => $tag,
                'date' => time(),
                'expiryDate' => (time() + $lifetime),
                'serialized' => (int) $serialized,
            ], $this->getPrimaryKey('tmp_store'));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete(string $id): void
    {
        $this->db->delete('tmp_store', ['id' => $id]);
    }

    public function getById(string $id): bool
    {
        $item = $this->db->fetchAssociative('SELECT * FROM tmp_store WHERE id = ?', [$id]);

        if ($item) {
            if ($item['serialized']) {
                $item['data'] = self::unserializeData((string) $item['data']);
            }

            $item['serialized'] = (bool)$item['serialized'];
            $this->assignVariablesToModel($item);

            return true;
        }

        return false;
    }

    /**
     * Safely unserialize data stored in the tmp_store table.
     *
     * Restricts object instantiation to classes within the Pimcore namespace in order
     * to mitigate object-injection / gadget-chain attacks (CWE-502) in case an attacker
     * gains a write primitive to the tmp_store table (e.g. through a SQL injection).
     */
    private static function unserializeData(string $data): mixed
    {
        $allowedClasses = self::extractAllowedClasses($data);

        return @unserialize($data, ['allowed_classes' => $allowedClasses]);
    }

    /**
     * Extracts the list of class names referenced in a serialized payload and
     * returns only those whose fully-qualified name is in the Pimcore namespace.
     *
     * Any other class names found in the payload are not added to the allowlist,
     * which causes PHP to instantiate them as __PHP_Incomplete_Class on unserialize
     * and therefore prevents __wakeup() / __destruct() gadgets from those classes
     * from being executed.
     *
     * @return list<string>
     */
    private static function extractAllowedClasses(string $data): array
    {
        if (!preg_match_all('/(?:^|;|{)[OC]:\d+:"([^"]+)"/', $data, $matches)) {
            return [];
        }

        $allowed = [];
        foreach (array_unique($matches[1]) as $class) {
            if (str_starts_with($class, 'Pimcore\\')) {
                $allowed[] = $class;
            }
        }

        return $allowed;
    }

    public function getIdsByTag(string $tag): array
    {
        $items = $this->db->fetchFirstColumn('SELECT id FROM tmp_store WHERE tag = ?', [$tag]);

        return $items;
    }
}
