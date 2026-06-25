<?php

declare(strict_types=1);

namespace nestouille\itemsplus\form;

use Closure;
use JsonSerializable;
use pocketmine\form\Form;
use pocketmine\player\Player;
use function array_key_exists;
use function is_int;

final class SimpleForm implements Form, JsonSerializable{

    /** @var array<int, array{text: string}> */
    private array $buttons = [];

    /** @var Closure(Player, int): void */
    private Closure $callback;

    /**
     * @param callable(Player, int): void $callback
     */
    public function __construct(
        private string $title,
        private string $content,
        callable $callback
    ){
        $this->callback = Closure::fromCallable($callback);
    }

    public function addButton(string $text) : self{
        $this->buttons[] = ["text" => $text];
        return $this;
    }

    public function jsonSerialize() : array{
        return [
            "type" => "form",
            "title" => $this->title,
            "content" => $this->content,
            "buttons" => $this->buttons
        ];
    }

    public function handleResponse(Player $player, mixed $data) : void{
        if(!is_int($data) || !array_key_exists($data, $this->buttons)){
            return;
        }

        ($this->callback)($player, $data);
    }
}
