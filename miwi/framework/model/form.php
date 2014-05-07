<?php
/**
 * @package     Moomla.Legacy
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('MPATH_PLATFORM') or die;

abstract class MModelForm extends MModelLegacy
{

	protected $_forms = array();

	public function checkin($pk = null)
	{
		// Only attempt to check the row in if it exists.
		if ($pk)
		{
			$user = MFactory::getUser();

			// Get an instance of the row to checkin.
			$table = $this->getTable();
			if (!$table->load($pk))
			{
				$this->setError($table->getError());
				return false;
			}

			// Check if this is the user having previously checked out the row.
			if ($table->checked_out > 0 && $table->checked_out != $user->get('id') && !$user->authorise('core.admin', 'com_checkin'))
			{
				$this->setError(MText::_('MLIB_APPLICATION_ERROR_CHECKIN_USER_MISMATCH'));
				return false;
			}

			// Attempt to check the row in.
			if (!$table->checkin($pk))
			{
				$this->setError($table->getError());
				return false;
			}
		}

		return true;
	}

	public function checkout($pk = null)
	{
		// Only attempt to check the row in if it exists.
		if ($pk)
		{
			$user = MFactory::getUser();

			// Get an instance of the row to checkout.
			$table = $this->getTable();
			if (!$table->load($pk))
			{
				$this->setError($table->getError());
				return false;
			}

			// Check if this is the user having previously checked out the row.
			if ($table->checked_out > 0 && $table->checked_out != $user->get('id'))
			{
				$this->setError(MText::_('MLIB_APPLICATION_ERROR_CHECKOUT_USER_MISMATCH'));
				return false;
			}

			// Attempt to check the row out.
			if (!$table->checkout($user->get('id'), $pk))
			{
				$this->setError($table->getError());
				return false;
			}
		}

		return true;
	}

	abstract public function getForm($data = array(), $loadData = true);

	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false)
	{
		// Handle the optional arguments.
		$options['control'] = MArrayHelper::getValue($options, 'control', false);

		// Create a signature hash.
		$hash = md5($source . serialize($options));

		// Check if we can use a previously loaded form.
		if (isset($this->_forms[$hash]) && !$clear)
		{
			return $this->_forms[$hash];
		}

		// Get the form.
		MForm::addFormPath(MPATH_COMPONENT . '/models/forms');
		MForm::addFieldPath(MPATH_COMPONENT . '/models/fields');

		try
		{
			$form = MForm::getInstance($name, $source, $options, false, $xpath);

			if (isset($options['load_data']) && $options['load_data'])
			{
				// Get the data for the form.
				$data = $this->loadFormData();
			}
			else
			{
				$data = array();
			}

			// Allow for additional modification of the form, and events to be triggered.
			// We pass the data because plugins may require it.
			$this->preprocessForm($form, $data);

			// Load the data into the form after the plugins have operated.
			$form->bind($data);

		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			return false;
		}

		// Store the form for later.
		$this->_forms[$hash] = $form;

		return $form;
	}

	protected function loadFormData()
	{
		return array();
	}

	protected function preprocessForm(MForm $form, $data, $group = 'content')
	{
		// Import the appropriate plugin group.
		MPluginHelper::importPlugin($group);

		// Get the dispatcher.
		$dispatcher = MDispatcher::getInstance();

		// Trigger the form preparation event.
		$results = $dispatcher->trigger('onContentPrepareForm', array($form, $data));

		// Check for errors encountered while preparing the form.
		if (count($results) && in_array(false, $results, true))
		{
			// Get the last error.
			$error = $dispatcher->getError();

			if (!($error instanceof Exception))
			{
				throw new Exception($error);
			}
		}
	}

	public function validate($form, $data, $group = null)
	{
		// Filter and validate the form data.
		$data = $form->filter($data);
		$return = $form->validate($data, $group);

		// Check for an error.
		if ($return instanceof Exception)
		{
			$this->setError($return->getMessage());
			return false;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				$this->setError($message);
			}

			return false;
		}

		return $data;
	}
}