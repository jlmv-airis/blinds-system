<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CErpUser;
use App\Models\CErpInfoUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Dirape\Token\Token;

class CreateUsersFromTemplate extends Command
{
    protected $signature = 'users:create-from-template
        {--template-email= : Email del usuario plantilla}
        {--template-id= : ID del usuario plantilla}';

    protected $description = 'Crea usuarios copiando los permisos de un usuario plantilla';

    public function handle()
    {
        $users = [
            ['email' => 'ena.dela.torre@blinds-system.com', 'password' => 'Blind0032', 'name' => 'Ena de la Torre'],
            ['email' => 'direccion-comercial@blinds-system.com', 'password' => 'Blind0033', 'name' => 'Direccion Comercial'],
            ['email' => 'administracion@blinds-system.com', 'password' => 'Blind0034', 'name' => 'Administracion'],
            ['email' => 'laura.rojas@blinds-system.com', 'password' => 'Blind0035', 'name' => 'Laura Rojas'],
            ['email' => 'pamela.lanson@blinds-system.com', 'password' => 'Blind0036', 'name' => 'Pamela Lanson'],
            ['email' => 'produccion@blinds-system.com', 'password' => 'Blind0037', 'name' => 'Produccion'],
            ['email' => 'compras@blinds-system.com', 'password' => 'Blind0038', 'name' => 'Compras'],
            ['email' => 'embarques@blinds-system.com', 'password' => 'Blind0039', 'name' => 'Embarques'],
        ];

        $templateEmail = $this->option('template-email');
        $templateId = $this->option('template-id');

        if ($templateId) {
            $templateUser = CErpUser::find($templateId);
        } elseif ($templateEmail) {
            $templateUser = CErpUser::where('user_email', $templateEmail)->first();
        } else {
            $templateEmail = $this->ask('Email del usuario plantilla (cuyos permisos se copiaran)');
            $templateUser = CErpUser::where('user_email', $templateEmail)->first();
            if (!$templateUser) {
                $this->error("No se encontro el usuario con email: $templateEmail");
                return 1;
            }
        }

        if (!$templateUser) {
            $this->error('Usuario plantilla no encontrado');
            return 1;
        }

        $this->info("Usuario plantilla: {$templateUser->user_email} (ID: {$templateUser->id})");

        $templatePermissions = DB::table('d_erp_access_users')->where('user_id', $templateUser->id)->get();

        if (empty($templatePermissions)) {
            $this->warn('El usuario plantilla no tiene permisos asignados');
            if (!$this->confirm('Continuar de todas formas?')) {
                return 0;
            }
        }

        $this->info("Se copiaran " . count($templatePermissions) . " registros de permisos");

        foreach ($users as $userData) {
            $this->info("Creando usuario: {$userData['email']}...");

            $existingUser = CErpUser::where('user_email', $userData['email'])->first();
            if ($existingUser) {
                $this->warn("  El usuario ya existe (ID: {$existingUser->id}), actualizando contrasena y permisos");
                $existingUser->password = Hash::make($userData['password']);
                $existingUser->uid = $userData['password'];
                if (empty($existingUser->api_token)) {
                    $existingUser->api_token = (new Token())->Unique('c_erp_users', 'api_token', 60);
                }
                $existingUser->save();

                DB::table('d_erp_access_users')->where('user_id', $existingUser->id)->delete();

                foreach ($templatePermissions as $perm) {
                    DB::table('d_erp_access_users')->insert([
                        'user_id' => $existingUser->id,
                        'module_id' => $perm->module_id,
                        'submodule_id' => $perm->submodule_id,
                        'submodule_son_id' => $perm->submodule_son_id,
                    ]);
                }

                $this->info("  OK - actualizado");
                continue;
            }

            $token = (new Token())->Unique('c_erp_users', 'api_token', 60);

            $user = new CErpUser();
            $user->uid = $userData['password'];
            $user->user_email = $userData['email'];
            $user->password = Hash::make($userData['password']);
            $user->api_token = $token;
            $user->save();

            $info = new CErpInfoUser();
            $info->user_id = $user->id;
            $info->short_name = $userData['name'];
            $info->full_name = $userData['name'];
            $info->email = $userData['email'];
            $info->phone = '';
            $info->department_id = 1;
            $info->company_id = 1;
            $info->is_leader = 0;
            $info->is_agent = 0;
            $info->save();

            foreach ($templatePermissions as $perm) {
                DB::table('d_erp_access_users')->insert([
                    'user_id' => $user->id,
                    'module_id' => $perm->module_id,
                    'submodule_id' => $perm->submodule_id,
                    'submodule_son_id' => $perm->submodule_son_id,
                ]);
            }

            $this->info("  OK - ID: {$user->id}");
        }

        $this->info('Todos los usuarios creados/actualizados exitosamente.');
        return 0;
    }
}
