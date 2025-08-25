<?php

class ShotQuality {
    public float $arcQuality;
    public float $shortQuality;
    public float $longQuality;
    public ?bool $brick;
    public int $timestamp;
    public string $userId;

    public function __construct(
        float $arcQuality,
        float $shortQuality,
        float $longQuality,
        ?bool $brick,
        string $userId
    ) {
        $this->arcQuality = $arcQuality;
        $this->shortQuality = $shortQuality;
        $this->longQuality = $longQuality;
        $this->brick = $brick;
        $this->userId = $userId;
        $this->timestamp = time(); // Set the timestamp on creation
    }
}
