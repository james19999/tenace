<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costumer_contact_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_follow_ups')->default(3);
        });

        Schema::table('costumer_contact_histories', function (Blueprint $table) {
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable();
            $table->enum('follow_up_decision', ['continue', 'stop'])->nullable();
        });

        Schema::create('costumer_contact_message_templates', function (Blueprint $table) {
            $table->id();
            $table->enum('channel', ['whatsapp', 'sms', 'call']);
            $table->string('name');
            $table->text('body');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['channel', 'active']);
        });

        Schema::create('costumer_contact_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costumer_id')->unique()->constrained('costumers')->cascadeOnDelete();
            $table->string('calling_code', 8)->nullable();
            $table->dateTime('do_not_contact_at')->nullable();
            $table->text('do_not_contact_reason')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('costumer_contact_histories', function (Blueprint $table) {
            $table->foreignId('message_template_id')->nullable()->constrained('costumer_contact_message_templates')->nullOnDelete();
        });

        $now = now();
        DB::table('costumer_contact_message_templates')->insert([
            ['channel' => 'whatsapp', 'name' => 'Demande d’avis', 'body' => 'Bonjour {{name}}, nous aimerions recueillir votre avis sur votre expérience avec nous. Qu’avez-vous pensé de votre commande ? Merci !', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['channel' => 'whatsapp', 'name' => 'Relance courte', 'body' => 'Bonjour {{name}}, nous revenons vers vous pour connaître votre avis. Votre retour nous aiderait beaucoup. Merci !', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['channel' => 'sms', 'name' => 'Demande d’avis', 'body' => 'Bonjour {{name}}, pouvez-vous nous donner votre avis sur votre commande ? Merci.', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['channel' => 'sms', 'name' => 'Relance courte', 'body' => 'Bonjour {{name}}, nous attendons votre avis sur votre commande. Merci pour votre retour !', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['channel' => 'call', 'name' => 'Trame d’appel', 'body' => 'Bonjour {{name}}, je vous appelle pour recueillir votre avis sur votre dernière commande. Avez-vous un instant pour nous dire comment s’est passée votre expérience ?', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('costumer_contact_preferences');

        Schema::table('costumer_contact_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('message_template_id');
            $table->dropColumn(['sentiment', 'follow_up_decision']);
        });

        Schema::dropIfExists('costumer_contact_message_templates');

        Schema::table('costumer_contact_settings', function (Blueprint $table) {
            $table->dropColumn('max_follow_ups');
        });
    }
};
