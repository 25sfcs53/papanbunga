<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // This migration assumes MySQL. It will add a new ENUM column, populate it by mapping
        // existing values and names, then drop the old column and rename the new one.
        $driver = DB::getDriverName();

        // For MySQL we can add a new ENUM column then drop/rename. For other drivers
        // (SQLite used by tests) altering table columns (DROP/CHANGE) is often
        // unsupported; instead update the existing `type` column in-place.
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE components ADD COLUMN type_new ENUM('huruf_besar','huruf_kecil','angka','simbol','hiasan','kata_sambung') NULL AFTER name");

            $rows = DB::table('components')->select('id', 'name', 'type')->get();
            foreach ($rows as $row) {
                $name = (string) $row->name;
                $oldType = $row->type;
                $newType = 'hiasan';

                if ($oldType === 'huruf') {
                    if (preg_match('/^[0-9]+$/', $name)) {
                        $newType = 'angka';
                    } elseif (mb_strlen($name) === 1) {
                        if (mb_strtoupper($name, 'UTF-8') === $name) {
                            $newType = 'huruf_besar';
                        } else {
                            $newType = 'huruf_kecil';
                        }
                    } else {
                        $newType = 'huruf_kecil';
                    }
                } else {
                    if (mb_strlen($name) === 1 && preg_match('/[^A-Za-z0-9]/u', $name)) {
                        $newType = 'simbol';
                    } elseif (mb_strpos($name, ' ') !== false) {
                        $newType = 'kata_sambung';
                    } else {
                        $newType = 'hiasan';
                    }
                }

                DB::table('components')->where('id', $row->id)->update(['type_new' => $newType]);
            }

            DB::statement("ALTER TABLE components DROP COLUMN `type`");
            DB::statement("ALTER TABLE components CHANGE COLUMN type_new `type` ENUM('huruf_besar','huruf_kecil','angka','simbol','hiasan','kata_sambung') NOT NULL");
        } else {
            // SQLite / other drivers: update the existing `type` column directly
            $rows = DB::table('components')->select('id', 'name', 'type')->get();
            foreach ($rows as $row) {
                $name = (string) $row->name;
                $oldType = $row->type;
                $newType = 'hiasan';

                if ($oldType === 'huruf') {
                    if (preg_match('/^[0-9]+$/', $name)) {
                        $newType = 'angka';
                    } elseif (mb_strlen($name) === 1) {
                        if (mb_strtoupper($name, 'UTF-8') === $name) {
                            $newType = 'huruf_besar';
                        } else {
                            $newType = 'huruf_kecil';
                        }
                    } else {
                        $newType = 'huruf_kecil';
                    }
                } else {
                    if (mb_strlen($name) === 1 && preg_match('/[^A-Za-z0-9]/u', $name)) {
                        $newType = 'simbol';
                    } elseif (mb_strpos($name, ' ') !== false) {
                        $newType = 'kata_sambung';
                    } else {
                        $newType = 'hiasan';
                    }
                }

                DB::table('components')->where('id', $row->id)->update(['type' => $newType]);
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            DB::statement("ALTER TABLE components ADD COLUMN type_old VARCHAR(50) NULL AFTER name");
        } else {
            DB::statement("ALTER TABLE components ADD COLUMN type_old ENUM('huruf','hiasan') NULL AFTER name");
        }

        $rows = DB::table('components')->select('id', 'name', 'type')->get();
        foreach ($rows as $row) {
            $name = (string) $row->name;
            $type = $row->type;
            $old = 'hiasan';
            if (in_array($type, ['huruf_besar','huruf_kecil','angka'])) {
                $old = 'huruf';
            } else {
                $old = 'hiasan';
            }
            DB::table('components')->where('id', $row->id)->update(['type_old' => $old]);
        }

        DB::statement("ALTER TABLE components DROP COLUMN `type`");
        if ($driver !== 'mysql') {
            DB::statement("ALTER TABLE components CHANGE COLUMN type_old type VARCHAR(50)");
        } else {
            DB::statement("ALTER TABLE components CHANGE COLUMN type_old `type` ENUM('huruf','hiasan') NOT NULL");
        }
    }
};
