<?php

namespace Base3\Api;

interface IConversation
{
    /**
     * Führt eine Konversation mit multimodalem Inhalt.
     *
     * @param array $messages Array von Nachrichten. Jede Nachricht besteht aus:
     * [
     *   'role' => 'system' | 'user' | 'assistant' | 'function',
     *   'content' => mixed (z.B. Text, Bild etc.),
     * ]
     * @param array $context Kontext und Optionen (model, temperature, tools etc.)
     * @return string Antwort der KI als Klartext
     */
    public function chat(array $messages, array $context = []): string;

    /**
     * Gibt die vollständige rohe Antwort der KI zurück (inkl. Tool Calls, Funktionen, etc.).
     *
     * @param array $messages
     * @param array $context
     * @return mixed
     */
    public function raw(array $messages, array $context = []): mixed;

    /**
     * Gibt den Namen oder die Kennung des verwendeten KI-Modells zurück.
     *
     * @return string
     */
    public function getModel(): string;

    /**
     * Konfiguriert die KI zur Laufzeit.
     *
     * @param array $options
     * @return void
     */
    public function configure(array $options): void;

    /**
     * Optional: Gibt eine formatierte Tool-Anfrage zurück, z.B. für CRM-Anbindung
     *
     * Erkennt, ob eine Aktion durchzuführen ist, und gibt z. B. ein JSON für einen API-Call zurück.
     *
     * @param array $response Die rohe KI-Antwort
     * @return array|null Rückgabe einer Tool-Call-Anfrage oder null
     */
    public function extractToolCall(mixed $response): ?array;

    /**
     * Optional: Erkennt, ob die Antwort an den User zurückgegeben werden kann
     *
     * @param mixed $response
     * @return bool
     */
    public function isFinalResponse(mixed $response): bool;
}

