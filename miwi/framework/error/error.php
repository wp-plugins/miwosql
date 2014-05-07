<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');


// Error Definition: Illegal Options
define('MERROR_ILLEGAL_OPTIONS', 1);
// Error Definition: Callback does not exist
define('MERROR_CALLBACK_NOT_CALLABLE', 2);
// Error Definition: Illegal Handler
define('MERROR_ILLEGAL_MODE', 3);

abstract class MError {

    public static $legacy = false;

    protected static $levels = array(E_NOTICE => 'Notice', E_WARNING => 'Warning', E_ERROR => 'Error');

    protected static $handlers = array(
        E_NOTICE => array('mode' => 'message'),
        E_WARNING => array('mode' => 'message'),
        E_ERROR => array('mode' => 'message')
    );

    protected static $stack = array();

    public static function isError(& $object) {
        // Deprecation warning.
        MLog::add('MError::isError() is deprecated.', MLog::WARNING, 'deprecated');

        // Supports PHP 5 exception handling
        return $object instanceof Exception;
    }

    public static function getError($unset = false) {
        // Deprecation warning.
        MLog::add('MError::getError() is deprecated.', MLog::WARNING, 'deprecated');

        if (!isset(MError::$stack[0])) {
            return false;
        }

        if ($unset) {
            $error = array_shift(MError::$stack);
        }
        else {
            $error = & MError::$stack[0];
        }
        return $error;
    }

    public static function getErrors() {
        // Deprecation warning.
        MLog::add('MError::getErrors() is deprecated.', MLog::WARNING, 'deprecated');

        return MError::$stack;
    }

    public static function addToStack(MException &$e) {
        // Deprecation warning.
        MLog::add('MError::addToStack() is deprecated.', MLog::WARNING, 'deprecated');

        MError::$stack[] = & $e;
    }

    public static function raise($level, $code, $msg, $info = null, $backtrace = false) {
        // Deprecation warning.
        MLog::add('MError::raise() is deprecated.', MLog::WARNING, 'deprecated');

        mimport('framework.error.exception');

        // Build error object
        $exception = new MException($msg, $code, $level, $info, $backtrace);
        return MError::throwError($exception);
    }

    public static function throwError(&$exception) {
        // Deprecation warning.
        MLog::add('MError::throwError() is deprecated.', MLog::WARNING, 'deprecated');

        static $thrown = false;

        // If thrown is hit again, we've come back to MError in the middle of throwing another MError, so die!
        if ($thrown) {
            self::handleEcho($exception, array());
            // Inifite loop.
            mexit();
        }

        $thrown = true;
        $level = $exception->get('level');

        // See what to do with this kind of error
        $handler = MError::getErrorHandling($level);

        $function = 'handle' . ucfirst($handler['mode']);
        if (is_callable(array('MError', $function))) {
            $reference = call_user_func_array(array('MError', $function), array(&$exception, (isset($handler['options'])) ? $handler['options'] : array()));
        }
        else {
            // This is required to prevent a very unhelpful white-screen-of-death
            mexit(
                'MError::raise -> Static method MError::' . $function . ' does not exist.' . ' Contact a developer to debug' .
                '<br /><strong>Error was</strong> ' . '<br />' . $exception->getMessage()
            );
        }
        // We don't need to store the error, since MException already does that for us!
        // Remove loop check
        $thrown = false;

        return $reference;
    }

    public static function raiseError($code, $msg, $info = null) {
        // Deprecation warning.
        MLog::add('MError::raiseError() is deprecated.', MLog::WARNING, 'deprecated');

        return MError::raise(E_ERROR, $code, $msg, $info, true);
    }

    public static function raiseWarning($code, $msg, $info = null) {
        // Deprecation warning.
        MLog::add('MError::raiseWarning() is deprecated.', MLog::WARNING, 'deprecated');

        return MError::raise(E_WARNING, $code, $msg, $info);
    }

    public static function raiseNotice($code, $msg, $info = null) {
        // Deprecation warning.
        MLog::add('MError::raiseNotice() is deprecated.', MLog::WARNING, 'deprecated');

        return MError::raise(E_NOTICE, $code, $msg, $info);
    }

    public static function getErrorHandling($level) {
        // Deprecation warning.
        MLog::add('MError::getErrorHandling() is deprecated.', MLog::WARNING, 'deprecated');

        return MError::$handlers[$level];
    }

    public static function setErrorHandling($level, $mode, $options = null) {
        // Deprecation warning.
        MLog::add('MError::setErrorHandling() is deprecated.', MLog::WARNING, 'deprecated');

        $levels = MError::$levels;

        $function = 'handle' . ucfirst($mode);

        if (!is_callable(array('MError', $function))) {
            return MError::raiseError(E_ERROR, 'MError:' . MERROR_ILLEGAL_MODE, 'Error Handling mode is not known', 'Mode: ' . $mode . ' is not implemented.');
        }

        foreach ($levels as $eLevel => $eTitle) {
            if (($level & $eLevel) != $eLevel) {
                continue;
            }

            // Set callback options
            if ($mode == 'callback') {
                if (!is_array($options)) {
                    return MError::raiseError(E_ERROR, 'MError:' . MERROR_ILLEGAL_OPTIONS, 'Options for callback not valid');
                }

                if (!is_callable($options)) {
                    $tmp = array('GLOBAL');
                    if (is_array($options)) {
                        $tmp[0] = $options[0];
                        $tmp[1] = $options[1];
                    }
                    else {
                        $tmp[1] = $options;
                    }

                    return MError::raiseError(
                        E_ERROR,
                        'MError:' . MERROR_CALLBACK_NOT_CALLABLE,
                        'Function is not callable',
                        'Function:' . $tmp[1] . ' scope ' . $tmp[0] . '.'
                    );
                }
            }

            // Save settings
            MError::$handlers[$eLevel] = array('mode' => $mode);
            if ($options != null) {
                MError::$handlers[$eLevel]['options'] = $options;
            }
        }

        return true;
    }

    public static function attachHandler() {
        // Deprecation warning.
        MLog::add('MError::getErrorHandling() is deprecated.', MLog::WARNING, 'deprecated');

        set_error_handler(array('MError', 'customErrorHandler'));
    }

    public static function detachHandler() {
        // Deprecation warning.
        MLog::add('MError::detachHandler() is deprecated.', MLog::WARNING, 'deprecated');

        restore_error_handler();
    }

    public static function registerErrorLevel($level, $name, $handler = 'ignore') {
        // Deprecation warning.
        MLog::add('MError::registerErrorLevel() is deprecated.', MLog::WARNING, 'deprecated');

        if (isset(MError::$levels[$level])) {
            return false;
        }

        MError::$levels[$level] = $name;
        MError::setErrorHandling($level, $handler);

        return true;
    }

    public static function translateErrorLevel($level) {
        // Deprecation warning.
        MLog::add('MError::translateErrorLevel() is deprecated.', MLog::WARNING, 'deprecated');

        if (isset(MError::$levels[$level])) {
            return MError::$levels[$level];
        }

        return false;
    }

    public static function handleIgnore(&$error, $options) {
        // Deprecation warning.
        MLog::add('MError::handleIgnore() is deprecated.', MLog::WARNING, 'deprecated');

        return $error;
    }

    public static function handleEcho(&$error, $options) {
        // Deprecation warning.
        MLog::add('MError::handleEcho() is deprecated.', MLog::WARNING, 'deprecated');

        $level_human = MError::translateErrorLevel($error->get('level'));

        // If system debug is set, then output some more information.
        if (defined('MDEBUG')) {
            $backtrace = $error->getTrace();
            $trace = '';
            for ($i = count($backtrace) - 1; $i >= 0; $i--) {
                if (isset($backtrace[$i]['class'])) {
                    $trace .= sprintf("\n%s %s %s()", $backtrace[$i]['class'], $backtrace[$i]['type'], $backtrace[$i]['function']);
                }
                else {
                    $trace .= sprintf("\n%s()", $backtrace[$i]['function']);
                }

                if (isset($backtrace[$i]['file'])) {
                    $trace .= sprintf(' @ %s:%d', $backtrace[$i]['file'], $backtrace[$i]['line']);
                }
            }
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            // output as html
            echo "<br /><b>jos-$level_human</b>: "
                . $error->get('message') . "<br />\n"
                . (defined('MDEBUG') ? nl2br($trace) : '');
        }
        else {
            // Output as simple text
            if (defined('STDERR')) {
                fwrite(STDERR, "M$level_human: " . $error->get('message') . "\n");
                if (defined('MDEBUG')) {
                    fwrite(STDERR, $trace);
                }
            }
            else {
                echo "M$level_human: " . $error->get('message') . "\n";
                if (defined('MDEBUG')) {
                    echo $trace;
                }
            }
        }

        return $error;
    }

    public static function handleVerbose(&$error, $options) {
        // Deprecation warning.
        MLog::add('MError::handleVerbose() is deprecated.', MLog::WARNING, 'deprecated');

        $level_human = MError::translateErrorLevel($error->get('level'));
        $info = $error->get('info');

        if (isset($_SERVER['HTTP_HOST'])) {
            // Output as html
            echo "<br /><b>M$level_human</b>: " . $error->get('message') . "<br />\n";

            if ($info != null) {
                echo "&#160;&#160;&#160;" . $info . "<br />\n";
            }

            echo $error->getBacktrace(true);
        }
        else {
            // Output as simple text
            echo "M$level_human: " . $error->get('message') . "\n";
            if ($info != null) {
                echo "\t" . $info . "\n";
            }

        }

        return $error;
    }

    public static function handleDie(&$error, $options) {
        // Deprecation warning.
        MLog::add('MError::handleDie() is deprecated.', MLog::WARNING, 'deprecated');

        $level_human = MError::translateErrorLevel($error->get('level'));

        if (isset($_SERVER['HTTP_HOST'])) {
            // Output as html
            mexit("<br /><b>M$level_human</b>: " . $error->get('message') . "<br />\n");
        }
        else {
            // Output as simple text
            if (defined('STDERR')) {
                fwrite(STDERR, "M$level_human: " . $error->get('message') . "\n");
                mexit();
            }
            else {
                mexit("M$level_human: " . $error->get('message') . "\n");
            }
        }

        return $error;
    }

    public static function handleMessage(&$error, $options) {
        // Deprecation warning.
        MLog::add('MError::handleMessage() is deprecated.', MLog::WARNING, 'deprecated');

        $appl = MFactory::getApplication();
        $type = ($error->get('level') == E_NOTICE) ? 'notice' : 'error';
        $appl->enqueueMessage($error->get('message'), $type);

        return $error;
    }

    public static function handleLog(&$error, $options) {
        // Deprecation warning.
        MLog::add('MError::handleLog() is deprecated.', MLog::WARNING, 'deprecated');

        static $log;

        if ($log == null) {
            $fileName = date('Y-m-d') . '.error.log';
            $options['format'] = "{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}";
            $log = MLog::getInstance($fileName, $options);
        }

        $entry['level'] = $error->get('level');
        $entry['code'] = $error->get('code');
        $entry['message'] = str_replace(array("\r", "\n"), array('', '\\n'), $error->get('message'));
        $log->addEntry($entry);

        return $error;
    }

    public static function handleCallback(&$error, $options) {
        // Deprecation warning.
        MLog::add('MError::handleCallback() is deprecated.', MLog::WARNING, 'deprecated');

        return call_user_func($options, $error);
    }

    public static function customErrorPage(&$error) {
        // Deprecation warning.
        MLog::add('MError::customErrorPage() is deprecated.', MLog::WARNING, 'deprecated');

        // Initialise variables.
        $app = MFactory::getApplication();
        $document = MDocument::getInstance('error');
        if ($document) {
            $config = MFactory::getConfig();

            // Get the current template from the application
            $template = $app->getTemplate();

            // Push the error object into the document
            $document->setError($error);

            // If site is offline and it's a 404 error, just go to index (to see offline message, instead of 404)
            if ($error->getCode() == '404' && MFactory::getConfig()->get('offline') == 1) {
                MFactory::getApplication()->redirect('index.php');
            }

            @ob_end_clean();
            $document->setTitle(MText::_('Error') . ': ' . $error->get('code'));
            $data = $document->render(false, array('template' => $template, 'directory' => MPATH_THEMES, 'debug' => $config->get('debug')));

            // Failsafe to get the error displayed.
            if (empty($data)) {
                self::handleEcho($error, array());
            }
            else {
                // Do not allow cache
                MResponse::allowCache(false);

                MResponse::setBody($data);
                echo MResponse::toString();
            }
        }
        else {
            // Must echo the error since there is no document
            // This is a common use case for Command Line Interface applications.
            self::handleEcho($error, array());
        }
        $app->close(0);
    }

    public static function customErrorHandler($level, $msg) {
        // Deprecation warning.
        MLog::add('MError::customErrorHandler() is deprecated.', MLog::WARNING, 'deprecated');

        MError::raise($level, '', $msg);
    }

    public static function renderBacktrace($error) {
        // Deprecation warning.
        MLog::add('MError::renderBacktrace() is deprecated.', MLog::WARNING, 'deprecated');

        $contents = null;
        $backtrace = $error->getTrace();

        if (is_array($backtrace)) {
            ob_start();
            $j = 1;
            echo '<table cellpadding="0" cellspacing="0" class="Table">';
            echo '		<tr>';
            echo '				<td colspan="3" class="TD"><strong>Call stack</strong></td>';
            echo '		</tr>';
            echo '		<tr>';
            echo '				<td class="TD"><strong>#</strong></td>';
            echo '				<td class="TD"><strong>Function</strong></td>';
            echo '				<td class="TD"><strong>Location</strong></td>';
            echo '		</tr>';

            for ($i = count($backtrace) - 1; $i >= 0; $i--) {
                echo '		<tr>';
                echo '				<td class="TD">' . $j . '</td>';

                if (isset($backtrace[$i]['class'])) {
                    echo '		<td class="TD">' . $backtrace[$i]['class'] . $backtrace[$i]['type'] . $backtrace[$i]['function'] . '()</td>';
                }
                else {
                    echo '		<td class="TD">' . $backtrace[$i]['function'] . '()</td>';
                }

                if (isset($backtrace[$i]['file'])) {
                    echo '				<td class="TD">' . $backtrace[$i]['file'] . ':' . $backtrace[$i]['line'] . '</td>';
                }
                else {
                    echo '				<td class="TD">&#160;</td>';
                }

                echo '		</tr>';
                $j++;
            }

            echo '</table>';
            $contents = ob_get_contents();
            ob_end_clean();
        }

        return $contents;
    }
}
