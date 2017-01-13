<?php

require '../Inbox.php';

class InboxController extends RESTfulResponse
{
    private $API_VERSION = 1;    // Integer
    public $index = array();

    private $inbox;
    private $login;

    function __construct($db, $login)
    {
        $this->inbox = new Inbox($db, $login);
        $this->login = $login;
    }

    public function get($act)
    {
        $inbox = $this->inbox;

        $this->index['GET'] = new ControllerMap();
        $cm = $this->index['GET'];
        $this->index['GET']->register('inbox/version', function() {
            return $this->API_VERSION;
        });

        $this->index['GET']->register('inbox/dependency/[text]', function($args) use ($inbox) {
			return $inbox->getInbox($args[0]);
        });

        return $this->index['GET']->runControl($act['key'], $act['args']);
    }

    public function post($act)
    {
        $form = $this->form;
        $login = $this->login;

        $this->index['POST'] = new ControllerMap();
        $this->index['POST']->register('inbox', function($args) {
            
        });

        return $this->index['POST']->runControl($act['key'], $act['args']);
    }
}

