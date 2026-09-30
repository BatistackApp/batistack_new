<?php

namespace App\Exceptions\RH;

use Exception;

/**
 * Erreur métier liée à la gestion du code PIN d'un salarié :
 * adresse email absente, échec d'envoi de la notification, etc.
 */
class EmployeePinException extends Exception {}
