<?php

/*
class UserDeletedEvent extends BaseEvent {
    public function __construct(public readonly int $userId) {}
}

$em = new EventManager();

// Normaler Listener auf genaues Event
$em->on(UserDeletedEvent::class, function (UserDeletedEvent $event) {
    echo "Delete handler: User {$event->userId}\n";
});

// Wildcard-Listener (einmalig)
$em->once("Base3\\Event\\*", function (object $event) {
    echo "Wildcard: ".get_class($event)."\n";
});

$em->fire(new UserDeletedEvent(42));
 */

