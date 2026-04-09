<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024-2026 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\VersionBumper\Tests\Version\Action;

use EliasHaeussler\VersionBumper as Src;
use EliasHaeussler\VersionBumper\Tests;
use PHPUnit\Framework;

/**
 * ActionFactoryTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\Action\ActionFactory::class)]
final class ActionFactoryTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyAction $action;
    private Src\Version\Action\ActionFactory $subject;

    public function setUp(): void
    {
        $this->action = new Tests\Fixtures\Classes\DummyAction();
        $this->subject = new Src\Version\Action\ActionFactory([$this->action]);
    }

    #[Framework\Attributes\Test]
    public function getReturnsRequestedActionIfActionIsAvailableInFactory(): void
    {
        self::assertSame($this->action, $this->subject->get('dummy'));
    }

    #[Framework\Attributes\Test]
    public function getThrowsExceptionIfActionIsNotAvailableInFactory(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\ActionDoesNotExist('foo'),
        );

        $this->subject->get('foo');
    }
}
