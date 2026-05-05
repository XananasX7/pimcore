<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

/**
 * @internal
 */
final class CompositeIndex
{
    /**
     * Validates that a composite index identifier (index key or column name) contains
     * only safe characters, preventing SQL injection via crafted class definition imports.
     *
     * @throws \InvalidArgumentException if the identifier contains disallowed characters
     */
    public static function assertValidIdentifier(string $identifier): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid composite index identifier "%s": only alphanumeric characters and underscores are allowed.',
                    $identifier
                )
            );
        }
    }
}
