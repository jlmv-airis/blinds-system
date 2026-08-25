<?php
namespace App\classes;

class SetMechanism {

    public function mechanism($greaterWidth,$greaterHeight,$productID,$operationID,$division,$cassette) {
        $mechanism = null;
        switch ( (INT)$productID  ) {
            case 1: // Enrollable
                switch ( (INT)$operationID) {
                    case 1: // Manual
                        switch ( (INT)$division) {
                            case 1:
                                if( (INT)$cassette === 1) {
                                    $mechanism = 2; // SL8
                                } else {
                                    if( (DOUBLE)$greaterWidth >= 3.10 ) {
                                        $mechanism = 4; // R24
                                    } else {
                                        if( (DOUBLE)$greaterHeight < 2.20 ) {
                                            $mechanism = 5; // XROLL
                                        } else {
                                            $mechanism = 3; // SL16
                                        }
                                    }
                                }
                            break;
                            case 2:
                                if( (INT)$cassette === 1) {
                                    $mechanism = 2; // SL8--------
                                } else {
                                    if( (DOUBLE)$greaterWidth >= 3.10 ) {
                                        $mechanism = 4; // R24
                                    } else {
                                        $mechanism = 3; // SL16
                                    }
                                }
                            break;
                            case 3:
                                $mechanism = 2; // SL8
                            break;
                        }
                    break;
                    case 2: // Motorizado

                    break;
                }
            break;
            case 2: // Sheer
                switch ( (INT)$operationID) {
                    case 1: // Manual
                        switch ( (INT)$division ) {
                            case 1:
                                if( (DOUBLE)$greaterWidth >= 2.4) {
                                    $mechanism = 2; // SL8
                                } else {
                                    $mechanism = 6; // MECANISMO SHEER TAPAS METALICAS
                                }
                            break;
                            case 2:
                                $mechanism = 2; // SL8
                            break;
                            case 3:
                                $mechanism = 2; // SL8
                            break;
                        }
                    break;
                    case 2: // Motorizado

                    break;
                }
            break;
        }
        return $mechanism;
    }

}