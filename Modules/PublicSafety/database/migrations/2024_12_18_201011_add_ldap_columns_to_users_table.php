<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLdapColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * @return void
     */
    public function up()
    {
      
    }

    /**
     * Reverse the migrations.
     * 
     * @return void
     */
    public function down()
    {
       
    }

    /**
     * Compile a compatible "unique" SQL Server index constraint.
     * 
     * @param string $table
     * @param string $column 
     * 
     * @return string 
     */
    protected function compileUniqueSqlServerIndexStatement($table, $column)
    {
        return sprintf('create unique index %s on %s (%s) where %s is not null',
            implode('_', [$table, $column, 'unique']),
            $table,
            $column,
            $column
        );
    }
}
