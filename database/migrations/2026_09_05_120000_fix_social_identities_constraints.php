<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * social_identities.user_id was a bare bigInteger with no foreign key, so
     * deleting an account left the identity row behind; the next sign-in with that
     * provider found the orphan and passed its null user to Auth::login().
     *
     * provider_id was also unique on its own, which collides across providers as
     * soon as two of them hand out the same subject id. The pair is what has to be
     * unique.
     */
    public function up(): void
    {
        $this->removeOrphanedIdentities();
        $this->removeDuplicateIdentities();

        if ($this->hasIndex('social_identities_provider_id_unique')) {
            Schema::table('social_identities', function (Blueprint $table) {
                $table->dropUnique('social_identities_provider_id_unique');
            });
        }

        // users.id is an unsigned bigint; the referencing column has to match.
        Schema::table('social_identities', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        Schema::table('social_identities', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        if (! $this->hasIndex('social_identities_provider_name_provider_id_unique')) {
            Schema::table('social_identities', function (Blueprint $table) {
                $table->unique(['provider_name', 'provider_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('social_identities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if ($this->hasIndex('social_identities_provider_name_provider_id_unique')) {
            Schema::table('social_identities', function (Blueprint $table) {
                $table->dropUnique(['provider_name', 'provider_id']);
            });
        }

        Schema::table('social_identities', function (Blueprint $table) {
            $table->bigInteger('user_id')->change();
        });

        if (! $this->hasIndex('social_identities_provider_id_unique')) {
            Schema::table('social_identities', function (Blueprint $table) {
                $table->unique('provider_id');
            });
        }
    }

    /**
     * The foreign key cannot be added while rows point at users that no longer exist.
     */
    protected function removeOrphanedIdentities(): void
    {
        DB::table('social_identities')
            ->whereNull('user_id')
            ->orWhereNotIn('user_id', DB::table('users')->select('id'))
            ->delete();
    }

    /**
     * Nor can the composite unique index be added over pre-existing duplicates.
     * Keep the oldest row of each pair; the identity it names is the same either way.
     */
    protected function removeDuplicateIdentities(): void
    {
        $keep = DB::table('social_identities')
            ->selectRaw('min(id) as id')
            ->groupBy('provider_name', 'provider_id')
            ->pluck('id');

        DB::table('social_identities')->whereNotIn('id', $keep)->delete();
    }

    protected function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('social_identities'))
            ->contains(fn (array $index) => $index['name'] === $name);
    }
};
