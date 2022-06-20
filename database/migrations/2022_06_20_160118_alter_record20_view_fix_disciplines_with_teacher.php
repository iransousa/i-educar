<?php

use Illuminate\Database\Migrations\Migration;
use App\Support\Database\MigrationUtils;

return new class extends Migration
{
    use MigrationUtils;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->dropView('public.educacenso_record20');

        $this->executeSqlFile(
            __DIR__ . '/../sqls/views/public.educacenso_record20-2022-06-17.sql'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropView('public.educacenso_record20');

        $this->executeSqlFile(
            __DIR__ . '/../sqls/views/public.educacenso_record20-2022-05-19.sql'
        );
    }
};
