<?php
namespace App\classes;
use App\Models\BLog;

class logs {
    public function createMovementLog($user_id,$name_movement,$type_system_id,$log_module_id,$log_submodule_id,$type_log_movement_id,$identifier_type,$identifier_number,$description) {
        $BLog = new BLog();
        $BLog->user_id              = $user_id;
        $BLog->log                  = $name_movement;
        $BLog->type_system_id       = $type_system_id;
        $BLog->module_id            = $log_module_id;
        $BLog->submodule_id         = $log_submodule_id;
        $BLog->type_log_movement_id = $type_log_movement_id;
        $BLog->identifier_type      = $identifier_type;
        $BLog->identifier_number    = $identifier_number;
        $BLog->identifier_text      = $identifier_number;
        $BLog->description          = $description;
        $BLog->save();
        return;
    }
}