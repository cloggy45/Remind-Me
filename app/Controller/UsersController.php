<?php

App::uses('AppController', 'Controller');

class UsersController extends AppController
{
    public $helpers = array('Html', 'Form', 'Timezone.Timezone');
    public $components = array('Session', 'Auth', 'Email');
    public $uses = array('User', 'OpauthUser', 'Reminder');

    public $hasOne = "OpauthUser";

    private $address = "http://192.168.33.101/Remind-Me";

    public function beforeFilter()
    {
        $this->Auth->allow('*');

        $this->Auth->loginAction = array(
            'controller' => 'Users',
            'action' => 'login'
        );

        if (array_key_exists('password', $this->data)) {
            $this->set('password', $this->Auth->password($this->data['User']['password']));
        }
        $this->Auth->allow();
    }

    public function opauth_complete()
    {
        $opauthUid = $this->data['auth']['uid'];
        $opauthUsername = $this->data['auth']['info']['name'];

        $loginUser = array('id' => $opauthUid, 'username' => $opauthUsername);

        if ($this->OpauthUser->userExists($opauthUid)) {

            if ($this->Auth->login($loginUser)) {
                $this->Session->write('Auth.User.authType', 'opauth');

                $userTimezone = $this->OpauthUser->getOpauthUserDetails($opauthUid, 'timezone');
                $this->Session->write('Auth.User.timezone', $userTimezone);

                $this->redirect(array('controller' => 'Reminder', 'action' => 'get'));
            } else {
                $this->Session->setFlash('Unable to log in', 'failureFlash');
            }

        } else {

            if ($this->OpauthUser->save($loginUser, false)) {

                if ($this->Auth->login($loginUser)) {

                    $this->Session->write('User.authType', 'opauth');
                    $this->redirect(array('controller' => 'Reminder', 'action' => 'get'));

                } else {
                    $this->Session->setFlash('Unable to log in', 'failureFlash');
                }

            } else {
                $this->Session->setFlash('Unable to store user information, try again', 'failureFlash');
            }
        }
    }

    public function login()
    {
        if ($this->request->is('post')) {
            if ($this->Auth->login()) {

                $this->Session->write('Auth.User.username', $this->request->data['User']['username']);

                $fieldValue = $this->request->data['User']['username'];

                $userId = $this->User->getUserId('username', $fieldValue);

                $this->Session->write('Auth.User.authType', 'local');

                $registrationValid = $this->User->Registration->getRegValidStatus($userId);

                if (!$registrationValid) {

                    $this->Session->setFlash('Activate email first', 'failureFlash');
                    $this->Session->delete('User');

                    return $this->redirect($this->Auth->logout());

                } else {

                    $userTimezone = $this->User->getUserDetails($userId, 'timezone');
                    $this->Session->write("Auth.User.timezone", $userTimezone);

                    $this->redirect(array('controller' => 'Reminder', 'action' => 'get'));
                }

            } else {
                $this->Session->setFlash('Incorrect login information', 'failureFlash');
                $this->redirect(array('controller' => 'Users', 'action' => 'login'));
            }
        }
    }

    public function logout()
    {
        $this->Session->delete('Auth');
        $this->Session->delete('User');
        $this->Session->delete('Config');
        $this->redirect($this->Auth->logout());
    }

    // Need to clean this up :c
    public function settings()
    {
        if ($this->request->is('post')) {

            $settingsChanged = false;

            $userId = $this->Session->read('Auth.User.id');

            $this->User->id = $userId;

            if ($this->request->data['User']['Clear All']) {
                $this->User->Reminder->deleteAll(
                    array('Reminder.user_id' => $this->Session->read('Auth.User.id'), false)
                );
            }

            if (!empty($this->request->data['User']['email'])) {

                $this->User->set($this->request->data);
                $this->OpauthUser->set($this->request->data);

                $validUser = $this->User->validates(array('fieldList' => array('email')));
                $validOpauth = $this->OpauthUser->validates(array('fieldList' => array('email')));

                if ($this->Session->read('User.authType') == 'opauth') {

                    $this->OpauthUser->id = $userId;

                    if ($validOpauth && $validUser) {
                        $this->OpauthUser->saveField('email', $this->request->data['User']['email'], false);
                        $settingsChanged = true;
                    }

                } else {

                    if ($validUser && $validOpauth) {
                        $this->User->saveField('email', $this->request->data['User']['email'], false);
                        $settingsChanged = true;
                    }
                }
            }

            if (!empty($this->request->data['User']['password'])) {

                if ($this->User->saveField('password', $this->request->data['User']['password'], true)) {
                    $settingsChanged = true;
                }
            }

            if ($this->request->data['User']['timezone'] !== "empty") {

                if ($this->Session->read('User.authType') == 'opauth') {

                    $this->OpauthUser->id = $userId;

                    if ($this->OpauthUser->saveField('timezone', $this->request->data['User']['timezone'], false)) {
                        $this->Session->write('Auth.User.timezone', $this->request->data['User']['timezone']);
                        $settingsChanged = true;
                    }

                } else {
                    if ($this->User->saveField('timezone', $this->request->data['User']['timezone'], true)) {
                        $this->Session->write('Auth.User.timezone', $this->request->data['User']['timezone']);
                        $settingsChanged = true;
                    }
                }
            }

            if ($this->request->data['User']['Clear All']) {
                $settingsChanged = true;
                $this->Session->setFlash('Cleared all reminders', 'successFlash');
            }

            if ($settingsChanged) {
                $this->Session->setFlash('Settings Changed', 'successFlash');

            } else {
                $this->Session->setFlash('Nothing to update', 'failureFlash');
            }
        }
    }

    public function deleteAccount()
    {
        $id = $this->Session->read('Auth.User.id');

        $this->User->delete($id);

        $this->User->Registration->deleteAll(array('user_id' => $id), false);
        $this->User->Reminder->deleteAll(array('user_id' => $id), false);
        $this->OpauthUser->deleteAll(array('id' => $id), false);

        $this->Session->delete('User');

        $this->redirect(
            array(
                'controller' => 'Users',
                'action' => 'login'
            )
        );
    }

    public function resetPassword()
    {
        $this->set('jsIncludes', array('formValidation'));

        if (count($this->request->params['named']) == 1) {

            $tempHash = $this->request->params['named']['hash'];

            $this->Session->write('Config.id', $this->User->getUserId('email_hash', $tempHash));
            $this->Session->write('Config.hash', $tempHash);

            $tempId = $this->Session->read('Config.id');

            if (empty($tempId)) {
                $this->Session->destroy();
                $this->Session->setFlash('Link is no longer valid', 'failureFlash');
                $this->redirect('login');
            }
        };

        if ($this->request->is('post')) {

            $id = $this->Session->read('Config.id');

            $this->User->id = $id;

            if ($this->User->saveField('password', $this->request->data['User']['password'], true)) {

                $this->Session->setFlash('Password successfully changed', 'successFlash');

                $email = $this->User->getUserDetails($id, 'email');
                $email += $this->Session->read('Config.time');

                $this->User->saveEmailHash($id, $email);

                $this->redirect('login');
            }
        }
    }

    public function sendResetEmail()
    {
        if ($this->request->is('post')) {

            $email = $this->request->data['User']['resetEmail'];

            $emailExists = $this->User->checkValueExists('email', $email);

            $id = $this->User->getUserId('email', $email);

            if ($emailExists && !$this->User->Registration->getRegValidStatus($id)) {

                $this->Session->setFlash('You must activate your account before changing passwords', 'failureFlash');

            } elseif ($emailExists) {

                $this->redirect(
                    array(
                        'controller' => 'Users',
                        'action' => 'sendActivationEmail',
                        'type' => 'reset',
                        'id' => $id
                    )
                );

            } else {
                $this->Session->setFlash('Account does not exist', 'failureFlash');
            }
        }
    }

    public function activateAccount()
    {
        $hash = $this->request->params['named']['hash'];

        $isHashValid = $this->User->Registration->setRegIsValidStatus($hash);

        if ($isHashValid) {

            $this->Session->setFlash('Account Validated', 'successFlash');

            $this->redirect(
                array(
                    'controller' => 'Users',
                    'action' => 'login'
                )
            );

        } else {
            $this->Session->setFlash('Account is not valid', 'failureFlash');
        }
    }

    public function sendActivationEmail()
    {
        require_once APP . 'Config/SendGridAuth.php';

        $id = $this->request->params['named']['id'];
        $emailType = $this->request->params['named']['type'];

        $this->Email->smtpOptions = $sendAuth;
        $this->Email->delivery = 'smtp';
        $this->Email->from = 'someemail@googlemail.com';
        $this->Email->to = $this->User->getUserDetails($id, 'email');

        $this->set('username', $this->User->getUserDetails($id, 'username'));

        if ($emailType == "activation") {

            $this->set('hash', $this->User->Registration->getEmailHash($id));
            $this->set('address', $this->address . '/Users/activateAccount/hash:');

            $this->Email->subject = 'Please Activate Email';

            $this->Email->template = 'registration_activation';
            $this->Email->sendAs = 'both';
            $this->Email->send();

            $this->Session->setFlash('Activation email sent', 'successFlash');

        } elseif ($emailType == "reset") {

            $this->set('hash', $this->User->getEmailHash($id));
            $this->set('address', $this->address . '/Users/resetPassword/hash:');

            $this->Email->subject = 'Reset Password';
            $this->Email->template = 'password_reset';
            $this->Email->sendAs = 'both';
            $this->Email->send();

            $this->Session->setFlash('Reset instructions sent', 'successFlash');
        }

        $this->redirect(
            array(
                'controller' => 'Users',
                'action' => 'login',
            )
        );
    }

    public function register()
    {
        $this->set('jsIncludes', array('formValidation', 'user-views/register'));

        if ($this->request->is('post')) {

            $this->User->set($this->request->data);
            $this->OpauthUser->set($this->request->data);

            $validUser = $this->User->validates();
            $validOpauth = $this->OpauthUser->validates();

            if ($validUser && $validOpauth) {

                $this->User->save($this->request->data, false);

                $id = $this->User->id;
                $email = $this->request->data['User']['email'];
                $time = $this->Session->read('Config.time');

                $email += $time;

                $saveHashedEmail = $this->User->Registration->saveEmailAsHash($id, $email);

                if (!$saveHashedEmail) {
                    CakeLog::write('Error', 'UsersController: Unable to save hashed email');
                }

                $this->User->saveEmailHash($id, $email);

                $this->redirect(
                    array(
                        'controller' => 'Users',
                        'action' => 'sendActivationEmail',
                        'type' => "activation",
                        'id' => $this->User->id
                    )
                );

            } else {

                $errors = $this->User->validationErrors;
                $errors += $this->OpauthUser->validationErrors;

                foreach ($errors as $error) {
                    $this->Session->setFlash($error, 'failureFlash');
                }
            }
        }
    }
}


?>