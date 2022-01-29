<?php

namespace Drupal\forms_module\Forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Egulias\EmailValidator\EmailValidator;

/**
 * Implements the Contact form controller.
 * 
 * @see \Drupal\Core\Form\FormBase
 */

class Contact extends FormBase {

  protected $database;
  protected $currentUser;
  protected $emailValidator;

  public function __construct(Connection $database, AccountInterface $current_user, EmailValidator $email_validator) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->emailValidator = $email_validator;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('email.validator')
    );
  }

  public function getFormId() {

    return 'forms_module_contact';

  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#description' => $this->t('Your first name'),
      '#required' => TRUE,
    ];
    
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#description' => $this->t('Your last name'),
      '#required' => TRUE,
    ];

    $form['user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Your email'),
      '#required' => TRUE,
    ];

    $form['tel_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Telephone number'),
      '#maxlength' => 64,
      '#required' => TRUE,
    ];

    $form['message_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message Subject'),
      '#required' => TRUE, 
    ];

    $form['message_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Your message'),
      '#cols' => 40,
      '#rows' => 5,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    //Email validator
    $email = $form_state->getValue('user_email');
    if (!$this->emailValidator->isValid($email)) {
      $form_state->setErrorByName('user_email', $this->t('%email is not a valid email adress.', ['%email' => $email]));
    }

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $this->database->insert('forms_module_contact')
      ->fields([
        'first_name' => $form_state->getValue('first_name'),
        'last_name' => $form_state->getValue('last_name'),
        'email' => $form_state->getValue('user_email'),
        'tel_number' => $form_state->getValue('tel_number'),
        'msg_subject' => $form_state->getValue('message_subject'),
        'msg_body' => $form_state->getValue('message_body'),
        'uid' => $this->currentUser->id(),
        'ip' => \Drupal::request()->getClientIP(),
        'timestamp' => REQUEST_TIME,
      ])
    ->execute();

    drupal_set_message($this->t('The form has been submitted correctly'));

    \Drupal::logger('forms_module')
      ->notice('New Simple Form entry from user %firstName %lastName inserted %subject.',
    [
      '%firstName' => $form_state->getValue('first_name'),
      '%lastName' => $form_state->getValue('last_name'),
      '%subject' => $form_state->getValue('message_subject'),
    ]);

  }

}