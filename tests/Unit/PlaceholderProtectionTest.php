<?php

declare(strict_types=1);

use JanakKapadia\InstaTranslate\TranslationManager;

it('masks variables prefixed with colon', function () {
    $manager = new TranslationManager;

    $chunk = [
        'welcome' => 'Hello :name, welcome back!',
        'validation' => 'The :attribute must be at least :min characters.',
        'uppercase' => 'Field :ATTRIBUTE is required.',
        'compound' => 'Sent to :first_name :last_name.',
    ];

    $result = $manager->maskPlaceholdersInChunk($chunk);

    expect($result['masked']['welcome'])->toBe('Hello __IT_VAR_0__, welcome back!')
        ->and($result['masked']['validation'])->toBe('The __IT_VAR_1__ must be at least __IT_VAR_2__ characters.')
        ->and($result['masked']['uppercase'])->toBe('Field __IT_VAR_3__ is required.')
        ->and($result['masked']['compound'])->toBe('Sent to __IT_VAR_4__ __IT_VAR_5__.')
        ->and($result['token_map'])->toBe([
            '__IT_VAR_0__' => ':name',
            '__IT_VAR_1__' => ':attribute',
            '__IT_VAR_2__' => ':min',
            '__IT_VAR_3__' => ':ATTRIBUTE',
            '__IT_VAR_4__' => ':first_name',
            '__IT_VAR_5__' => ':last_name',
        ]);
});

it('masks variables under curly braces', function () {
    $manager = new TranslationManager;

    $chunk = [
        'single_brace' => 'Welcome {name}, your balance is {balance}.',
        'with_spaces' => 'Hello { user_name }, your score is { score }.',
        'double_brace' => 'Remaining: {{count}} items ({{ count }}).',
        'numeric' => 'Item {0} of {1}.',
        'php_style' => 'Greetings {$name} and {{ $email }}.',
    ];

    $result = $manager->maskPlaceholdersInChunk($chunk);

    expect($result['masked']['single_brace'])->toBe('Welcome __IT_VAR_0__, your balance is __IT_VAR_1__.')
        ->and($result['masked']['with_spaces'])->toBe('Hello __IT_VAR_2__, your score is __IT_VAR_3__.')
        ->and($result['masked']['double_brace'])->toBe('Remaining: __IT_VAR_4__ items (__IT_VAR_5__).')
        ->and($result['masked']['numeric'])->toBe('Item __IT_VAR_6__ of __IT_VAR_7__.')
        ->and($result['masked']['php_style'])->toBe('Greetings __IT_VAR_8__ and __IT_VAR_9__.')
        ->and($result['token_map']['__IT_VAR_0__'])->toBe('{name}')
        ->and($result['token_map']['__IT_VAR_1__'])->toBe('{balance}')
        ->and($result['token_map']['__IT_VAR_4__'])->toBe('{{count}}')
        ->and($result['token_map']['__IT_VAR_5__'])->toBe('{{ count }}')
        ->and($result['token_map']['__IT_VAR_6__'])->toBe('{0}')
        ->and($result['token_map']['__IT_VAR_7__'])->toBe('{1}')
        ->and($result['token_map']['__IT_VAR_8__'])->toBe('{$name}')
        ->and($result['token_map']['__IT_VAR_9__'])->toBe('{{ $email }}');
});

it('reuses tokens for repeated placeholders across chunk', function () {
    $manager = new TranslationManager;

    $chunk = [
        'key1' => 'Hello :name! Did you know :name is great?',
        'key2' => 'Welcome back, :name.',
    ];

    $result = $manager->maskPlaceholdersInChunk($chunk);

    expect($result['masked']['key1'])->toBe('Hello __IT_VAR_0__! Did you know __IT_VAR_0__ is great?')
        ->and($result['masked']['key2'])->toBe('Welcome back, __IT_VAR_0__.')
        ->and($result['token_map'])->toHaveCount(1)
        ->and($result['token_map']['__IT_VAR_0__'])->toBe(':name');
});

it('does not mask non-placeholder colons', function () {
    $manager = new TranslationManager;

    $chunk = [
        'time' => 'The time is 10:30 AM.',
        'url' => 'Visit https://example.com/test for details.',
        'note' => 'Note: this is important.',
        'class_constant' => 'App::class and foo::bar.',
        'css' => 'color: #fff; margin: 10px;',
    ];

    $result = $manager->maskPlaceholdersInChunk($chunk);

    expect($result['masked'])->toBe($chunk)
        ->and($result['token_map'])->toBeEmpty();
});

it('masks single string placeholders via maskPlaceholdersInString', function () {
    $manager = new TranslationManager;

    $result = $manager->maskPlaceholdersInString('Hello :name, your code is {code}!');

    expect($result['masked'])->toBe('Hello __IT_VAR_0__, your code is __IT_VAR_1__!')
        ->and($result['token_map'])->toBe([
            '__IT_VAR_0__' => ':name',
            '__IT_VAR_1__' => '{code}',
        ]);
});

it('restores placeholders in translated content', function () {
    $manager = new TranslationManager;

    $tokenMap = [
        '__IT_VAR_0__' => ':name',
        '__IT_VAR_1__' => ':count',
        '__IT_VAR_2__' => '{date}',
    ];

    $translated = [
        'welcome' => 'Hola __IT_VAR_0__, tienes __IT_VAR_1__ mensajes.',
        'date' => 'Fecha de vencimiento: __IT_VAR_2__.',
    ];

    $restored = $manager->restorePlaceholders($translated, $tokenMap);

    expect($restored)->toBe([
        'welcome' => 'Hola :name, tienes :count mensajes.',
        'date' => 'Fecha de vencimiento: {date}.',
    ]);
});

it('resiliently restores placeholders when AI introduces whitespace or lowercase variations', function () {
    $manager = new TranslationManager;

    $tokenMap = [
        '__IT_VAR_0__' => ':name',
        '__IT_VAR_1__' => '{count}',
        '__IT_VAR_2__' => ':attribute',
    ];

    $translated = [
        'with_spaces' => 'Hola __ IT_VAR_0 __, bienvenido!',
        'lowercase' => 'Tienes __it_var_1__ notificaciones.',
        'single_underscore' => 'El _IT_VAR_2_ es obligatorio.',
    ];

    $restored = $manager->restorePlaceholders($translated, $tokenMap);

    expect($restored)->toBe([
        'with_spaces' => 'Hola :name, bienvenido!',
        'lowercase' => 'Tienes {count} notificaciones.',
        'single_underscore' => 'El :attribute es obligatorio.',
    ]);
});

it('restores placeholders in multiple translation variations', function () {
    $manager = new TranslationManager;

    $tokenMap = [
        '__IT_VAR_0__' => ':name',
        '__IT_VAR_1__' => '{count}',
    ];

    $translated = [
        'welcome' => [
            'Hola __IT_VAR_0__, tienes __IT_VAR_1__ mensajes.',
            'Bienvenido __IT_VAR_0__, hay __IT_VAR_1__ mensajes pendientes.',
            'Saludos __IT_VAR_0__, cuentas con __IT_VAR_1__ mensajes.',
        ],
    ];

    $restored = $manager->restorePlaceholders($translated, $tokenMap);

    expect($restored)->toBe([
        'welcome' => [
            'Hola :name, tienes {count} mensajes.',
            'Bienvenido :name, hay {count} mensajes pendientes.',
            'Saludos :name, cuentas con {count} mensajes.',
        ],
    ]);
});

it('leaves strings without placeholders untouched during restore', function () {
    $manager = new TranslationManager;

    $translated = [
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
    ];

    $restored = $manager->restorePlaceholders($translated, []);

    expect($restored)->toBe($translated);
});

it('masks chunk before sending to AI and restores placeholders on response in translateChunk', function () {
    $manager = new class extends TranslationManager
    {
        public ?string $receivedPrompt = null;

        public function callAi(string $prompt, string $model, string $provider): ?array
        {
            $this->receivedPrompt = $prompt;

            return [
                'welcome' => 'Hola __IT_VAR_0__, tienes __IT_VAR_1__ mensajes.',
            ];
        }
    };

    $result = $manager->translateChunk([
        'welcome' => 'Hello :name, you have {count} messages.',
    ], 'es', 'claude', 'en');

    expect($manager->receivedPrompt)->toContain('__IT_VAR_0__')
        ->and($manager->receivedPrompt)->toContain('__IT_VAR_1__')
        ->and($manager->receivedPrompt)->not->toContain('Hello :name')
        ->and($result)->toBe([
            'welcome' => 'Hola :name, tienes {count} mensajes.',
        ]);
});

it('masks string before sending to AI and restores placeholders on response in translateKeyForLocales', function () {
    $manager = new class extends TranslationManager
    {
        public ?string $receivedPrompt = null;

        public function callAi(string $prompt, string $model, string $provider): ?array
        {
            $this->receivedPrompt = $prompt;

            return [
                'es' => 'Hola __IT_VAR_0__, tienes __IT_VAR_1__ mensajes.',
                'fr' => 'Bonjour __IT_VAR_0__, vous avez __IT_VAR_1__ messages.',
            ];
        }
    };

    $result = $manager->translateKeyForLocales(
        'welcome',
        'Hello :name, you have {count} messages.',
        ['es', 'fr'],
        'claude',
        'en',
    );

    expect($manager->receivedPrompt)->toContain('__IT_VAR_0__')
        ->and($manager->receivedPrompt)->toContain('__IT_VAR_1__')
        ->and($result)->toBe([
            'es' => 'Hola :name, tienes {count} mensajes.',
            'fr' => 'Bonjour :name, vous avez {count} messages.',
        ]);
});
