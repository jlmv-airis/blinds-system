<?php
namespace App\classes;

use App\Models\DViewNotification;
use App\Models\ENotification;
use Illuminate\Support\Facades\DB;

class Notifications {
    public function createNewNotification($identifier,$type_notification_id,$assign_user_id,$users_send_id,$message,$to) {
        try {
            // Creamos la notificacion
            $ENotification = new ENotification();
            $ENotification->title                 = $message['title'];
            $ENotification->description           = $message['description'];
            $ENotification->type_notifications_id = $type_notification_id;
            $ENotification->icon                  = $message['icon'];
            $ENotification->icon_color            = $message['icon_color'];
            if( $assign_user_id != 0 ) { $ENotification->assign_user_id = $assign_user_id; }
            if( $identifier != 0 ) {  $ENotification->identifier = $identifier; }
            $ENotification->to = $to;
            $ENotification->save();
            // generqamos las vistas para los usuarios
            foreach ($users_send_id as $user) {
                // Guardamos la visualisacion de la notificacion
                $DViewNotification = new DViewNotification();
                $DViewNotification->notifications_id = $ENotification->id;
                $DViewNotification->user_id = $user['id'];
                $DViewNotification->save();
            }
            // creamos la notificacion para enviar
            $notification_send = ENotification::select('e_notifications.id','e_notifications.title','e_notifications.description','e_notifications.icon','e_notifications.icon_color','e_notifications.type_notifications_id','e_notifications.created_at',DB::raw('0 as is_view'),'e_notifications.to','c_erp_info_users.short_name','c_erp_info_users.user_img','c_erp_info_users.color as user_color')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_notifications.assign_user_id')
            ->where('e_notifications.id',$ENotification->id)
            ->first()
            ->toArray();
            return $notification_send;
            //code...
        } catch (\Throwable $th) {
            dd($th);
        }
    }
}