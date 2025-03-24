<?php
use Arch\Arch;
use Arch\Arch\Preset;
use Arch\Arch\Preset\Laravel;
use Arch\Arch\Preset\Security;
use Arch\Arch\Preset\Testing;
use Arch\Arch\Preset\Testing\Unit;

arch()->preset()->security();
arch()->preset()->laravel();