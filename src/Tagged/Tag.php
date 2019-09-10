<?php
/**
 * This file is part of the Tagged package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Tagged;

use DecodeLabs\Collections\AttributeContainer;

interface Tag extends Markup, AttributeContainer, \ArrayAccess
{
    public function setName(string $name): Tag;
    public function getName(): string;

    public function setId(?string $id): Tag;
    public function getId(): ?string;

    public function isInline(): bool;
    public function isBlock(): bool;

    public function open(): string;
    public function close(): string;

    public function renderWith($content=null): ?Markup;

    public function setRenderEmpty(bool $render): Tag;
    public function willRenderEmpty(): bool;
}
