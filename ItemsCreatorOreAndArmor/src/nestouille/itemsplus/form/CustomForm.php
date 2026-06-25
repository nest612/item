<?php

declare(strict_types=1);

namespace nestouille\itemsplus\form;

use Closure;
use JsonSerializable;
use pocketmine\form\Form;
use pocketmine\player\Player;
use function is_array;

final class CustomForm implements Form, JsonSerializable{

    /** @var array<int, array<string, mixed>> */
    private array $content = [];

    /** @var Closure(Player, array<int, mixed>): void */
    private Closure $callback;

    /**
     * @param callable(Player, array<int, mixed>): void $callback
     */
    public function __construct(private string $title, callable $callback){
        $this->callback = Closure::fromCallable($callback);
    }

    public function addLabel(string $text) : self{
        $this->content[] = [
            "type" => "label",
            "text" => $text
        ];
        return $this;
    }

    public function addInput(string $text, string $placeholder = "", string $default = "") : self{
        $this->content[] = [
            "type" => "input",
            "text" => $text,
            "placeholder" => $placeholder,
            "default" => $default
        ];
        return $this;
    }

    public function addToggle(string $text, bool $default = false) : self{
        $this->content[] = [
            "type" => "toggle",
            "text" => $text,
            "default" => $default
        ];
        return $this;
    }

    public function jsonSerialize() : array{
        return [
            "type" => "custom_form",
            "title" => $this->title,
            "content" => $this->content
        ];
    }

    public function handleResponse(Player $player, mixed $data) : void{
        if(!is_array($data)){
            return;
        }

        ($this->callback)($player, $data);
    }
}
