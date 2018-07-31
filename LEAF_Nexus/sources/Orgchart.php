<?php
/*
 * As a work of the United States government, this project is in the public domain within the United States.
 */

/************************
    Orgchart
    Date: September 1, 2011
    
*/

namespace Orgchart;

class Orgchart
{
    public $employee;

    public $position;

    public $group;

    private $db;

    private $login;

    public function __construct($db, $login)
    {
        $this->db = $db;
        $this->login = $login;

        $this->employee = new Orgchart\Employee($db, $login);
        $this->position = new Orgchart\Position($db, $login);
        $this->group = new Orgchart\Group($db, $login);
    }

    public function getEmployeeDossierByLogin($userID)
    {
    }
}
