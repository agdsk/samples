<?php

namespace AppBundle\Library;

class DirectoryService
{
    private $ds;

    private $last_error;

    public function __construct($server) {
        $this->server = $server;
    }

    public function connect()
    {
        $this->ds = ldap_connect($this->server);

        ldap_set_option($this->ds, LDAP_OPT_REFERRALS, false);
        ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3);
    }

    private function disconnect()
    {
        ldap_close($this->ds);
    }

    public function debug($message)
    {
        //echo $message . '<br>';
    }

    public function getLastError()
    {
        return $this->last_error;
    }

    private function setLastError($error)
    {
        $this->debug('Error: ' . $error);

        $this->last_error = $error;
    }

    public function tidyUser($user)
    {
        $tidyuser = [
            'opid'              => array_key_exists('cn', $user) ? $user['cn'] : '',
            'first_name'        => array_key_exists('givenname', $user) ? $user['givenname'] : '',
            'last_name'         => array_key_exists('sn', $user) ? $user['sn'] : '',
            'title'             => array_key_exists('title', $user) ? $user['title'] : '',
            'department'        => array_key_exists('department', $user) ? $user['department'] : '',
            'distinguishedname' => $user['distinguishedname'],
            'email'             => '',
        ];

        if (array_key_exists('userprincipalname', $user)) {
            $tidyuser['email'] = strtolower($user['userprincipalname']);
        }

        if (array_key_exists('mail', $user)) {
            $tidyuser['email'] = strtolower($user['mail']);
        }

        return $tidyuser;
    }

    public function authenticate($opid, $password)
    {
        $this->debug('Authenticating for ' . $opid . ' and ' . $password);

        $this->connect();

        try {
            $this->bindAnonymously();
        } catch (\Exception $e) {
            $this->setLastError('Could not contact authentication server, please try again later');

            return false;
        }

        if (!$user = $this->searchForUser($opid)) {
            $this->setLastError('User not found');

            return false;
        }

        $user = $this->tidyUser($user);

        try {
            $this->bindAsUser($user['distinguishedname'], $password);
        } catch (\Exception $e) {
            $this->setLastError('Incorrect password');

            return false;
        }

        $this->disconnect();

        return $user;
    }

    public function bindAsUser($dn, $password)
    {
        if ($password == '') {
            throw new \Exception('Blank password');
        }

        $this->bind($dn, $password);
    }

    public function bindAnonymously()
    {
        $this->debug('Binding anonymously');

        $this->bind('cn=ldapanonymous,ou=serviceaccounts,ou=resources,dc=flhosp,dc=net', 'xxx');
    }

    private function bind($dn, $password)
    {
        $this->debug('Binding as ' . $dn . ' with password ' . $password);

        if(!ldap_bind($this->ds, $dn, $password)) {
            throw new \Exception('Failed to bind');
        }
    }

    public function searchForUser($opid)
    {
        $this->debug('Searching for ' . $opid);

        $search_results = ldap_search($this->ds, "dc=flhosp,dc=net", "cn=" . $opid);

        $this->debug(ldap_count_entries($this->ds, $search_results) . ' entries found');

        if (ldap_count_entries($this->ds, $search_results) < 1) {
            $this->debug('User not found');

            return false;
        }

        $entries = ldap_get_entries($this->ds, $search_results);

        $user = $entries[0];

        foreach ($user as $k => $v) {
            if (is_numeric($k)) {
                unset($user[$k]);
            }

            if (is_array($v) && array_key_exists('count', $v) && $v['count'] == 1) {
                $user[$k] = $user[$k][0];
            }
        }

        return $user;
    }
}
