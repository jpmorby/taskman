<?php

/**
 * (C) Jon Morby 2025.  All Rights Reserved.
 */

namespace App\Support;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\App;

/**
 * Resolves the currency used to display monetary amounts.
 *
 * The currency is worked out the same way the locale is: a choice the visitor
 * has made (stored in the session by the currency toggle) always wins, and
 * otherwise we guess from the active locale before falling back to the
 * configured default.
 */
class CurrencyManager
{
    /**
     * The session key holding the visitor's explicit choice.
     */
    public const SESSION_KEY = 'currency';

    /**
     * A currency pinned for this request only, if one has been pinned.
     */
    protected ?string $pinned = null;

    public function __construct(
        protected Config $config,
        protected Session $session,
    ) {}

    /**
     * All of the currencies the toggle offers, keyed by ISO 4217 code.
     *
     * @return array<string, array<string, mixed>>
     */
    public function supported(): array
    {
        /** @var array<string, array<string, mixed>> $supported */
        $supported = $this->config->get('currency.supported', []);

        return $supported;
    }

    /**
     * The codes of all supported currencies.
     *
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_keys($this->supported());
    }

    /**
     * Determine whether the given code is one we can display.
     */
    public function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($this->normalise($code), $this->supported());
    }

    /**
     * Tidy a user supplied code into the form used as a config key.
     */
    public function normalise(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * The currency used when we know nothing about the visitor.
     */
    public function default(): string
    {
        $default = $this->config->get('currency.default');

        if (is_string($default) && $this->isSupported($default)) {
            return $this->normalise($default);
        }

        return $this->codes()[0] ?? 'GBP';
    }

    /**
     * Guess a currency from a locale, ignoring any choice the visitor made.
     */
    public function guess(?string $locale = null): string
    {
        $locale = str_replace('-', '_', $locale ?? App::getLocale());

        /** @var array<string, string> $map */
        $map = $this->config->get('currency.locales', []);

        // Prefer the most specific match, e.g. "fr_CA" before "fr".
        foreach ([$locale, explode('_', $locale)[0]] as $candidate) {
            if (isset($map[$candidate]) && $this->isSupported($map[$candidate])) {
                return $this->normalise($map[$candidate]);
            }
        }

        return $this->default();
    }

    /**
     * The currency the visitor has explicitly chosen, if any.
     */
    public function override(): ?string
    {
        $stored = $this->session->get(self::SESSION_KEY);

        if (is_string($stored) && $this->isSupported($stored)) {
            return $this->normalise($stored);
        }

        return null;
    }

    /**
     * Determine whether the visitor has overridden the locale based guess.
     */
    public function hasOverride(): bool
    {
        return $this->override() !== null;
    }

    /**
     * The currency to display amounts in.
     *
     * This is resolved on every call rather than cached, so a choice stored
     * part way through a request (or once the session has been started) is
     * picked up straight away.
     */
    public function current(): string
    {
        return $this->pinned ?? $this->override() ?? $this->guess();
    }

    /**
     * Use the given currency for the rest of this request only.
     */
    public function setCurrent(string $code): void
    {
        if ($this->isSupported($code)) {
            $this->pinned = $this->normalise($code);
        }
    }

    /**
     * Drop any currency pinned for the current request.
     *
     * Called at the start of each request so that a currency pinned by one
     * request cannot leak into the next where the container is reused.
     */
    public function flush(): void
    {
        $this->pinned = null;
    }

    /**
     * Remember the given currency for this visitor, overriding the guess.
     */
    public function store(string $code): void
    {
        if (! $this->isSupported($code)) {
            return;
        }

        $code = $this->normalise($code);

        $this->session->put(self::SESSION_KEY, $code);
        $this->pinned = $code;
    }

    /**
     * Drop the visitor's choice and fall back to the locale based guess.
     */
    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
        $this->pinned = null;
    }

    /**
     * The display settings for a currency.
     *
     * @return array<string, mixed>
     */
    public function definition(?string $code = null): array
    {
        $code = $code !== null && $this->isSupported($code)
            ? $this->normalise($code)
            : $this->current();

        return $this->supported()[$code] ?? [];
    }

    /**
     * The symbol a currency is written with, e.g. "£".
     */
    public function symbol(?string $code = null): string
    {
        $definition = $this->definition($code);

        return is_string($definition['symbol'] ?? null) ? $definition['symbol'] : '';
    }

    /**
     * The translated name of a currency, e.g. "British Pound".
     */
    public function name(?string $code = null): string
    {
        $definition = $this->definition($code);

        return is_string($definition['name'] ?? null) ? __($definition['name']) : '';
    }

    /**
     * Render an amount in the current (or given) currency.
     */
    public function format(int|float $amount, ?string $code = null): string
    {
        $definition = $this->definition($code);

        $number = number_format(
            abs($amount),
            (int) ($definition['decimals'] ?? 2),
            (string) ($definition['decimal_separator'] ?? '.'),
            (string) ($definition['thousands_separator'] ?? ','),
        );

        $symbol = is_string($definition['symbol'] ?? null) ? $definition['symbol'] : '';

        $formatted = ($definition['position'] ?? 'before') === 'after'
            ? $number.' '.$symbol
            : $symbol.$number;

        return ($amount < 0 ? '-' : '').trim($formatted);
    }
}
