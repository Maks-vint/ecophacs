<?php namespace WelterRocks\EcoPhacs;

/******************************************************************************

    EcoPhacs is a php class to control ecovacs api based devices
    Copyright (C) 2020  Oliver Welter  <oliver@welter.rocks>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.

*******************************************************************************/

declare(ticks = 1);

use WelterRocks\EcoPhacs\Callback;

class CLI
{
    private $pid = null;
    private $uid = null;
    private $gid = null;
    
    private $timestamp = null;
    private $process_title = null;
    private $process_priority = null;

    private $command = null;
    private $arguments = null;
    private $argumentcount = null;    
    private $environment = null;

    private $pathname = null;
    private $filename = null;
    private $lastmod = null;
    private $inode = null;
    
    private $signals = null;
    private $callbacks = null;
    
    private $child_pids = null;
    
    public function get_uid()
    {
        return $this->uid;
    }
    
    public function get_gid()
    {
        return $this->gid;
    }
    
    public function get_pid()
    {
        return $this->pid;
    }
    
    public function get_timestamp()
    {
        return $this->timestamp;
    }
    
    public function get_command()
    {
        return $this->command;
    }
    
    public function get_environment($key = null)
    {
        if (!$key)
            return $this->environment;
            
        if (!isset($this->environment[$key]))
            return null;
            
        return $this->environment[$key];
    }
    
    public function get_argument($arg = null)
    {
        if (!$arg)
            return $this->arguments;
            
        if (!isset($this->arguments[$arg]))
            return null;
            
        return $this->arguments[$arg];
    }
    
    public function get_argument_count($arg = null)
    {
        if (!$arg)
            return count($this->arguments); // Returns the UNIQUE count of arguments, not the total amount
        
        if (!isset($this->arguments[$arg]))
            return false;
            
        return count($this->arguments[$arg]);        
    }
    
    public function get_argument_childs($arg, $childcount = 1)
    {
        if (!isset($this->arguments[$arg]))
            return false;
            
        $childs = array();
        $running = false;
        $runcounter = 0;
        
        foreach ($this->arguments as $argument => $positions)
        {
            if ($arg == $argument)
            {
                $running = true;
                
                continue;
            }
            
            if ($running)
            {
                array_push($childs, $argument);
                
                $runcounter++;
                
                if ($runcounter == $childcount)
                {
                    $running = false;
                    $runcounter = 0;
                }
            }
        }
        
        return $childs;
    }
    
    public function has_argument($arg)
    {
        if (!isset($this->arguments[$arg]))
            return false;
            
        return true;
    }
    
    public function get_argumentcount()
    {
        return $this->argumentcount;
    }
    
    public function get_process_title()
    {
        return $this->process_title;
    }
    
    public function set_process_title($process_title)
    {
        if ($process_title)
        {
            $this->process_title = $process_title;
            
            cli_set_process_title($this->process_title);
            
            return cli_get_process_title();
        }
        
        return null;
    }
    
    public function get_pathname()
    {
        return $this->pathname;
    }
    
    public function get_filename()
    {
        return $this->filename;
    }
    
    public function get_inode()
    {
        return $this->inode;
    }
    
    public function get_lastmod()
    {
        return $this->lastmod;
    }
    
    public function handle_callbacks($signal)
    {
        $callbacks = $this->get_callback($signal);
        
        if ((!$callbacks) || (!is_array($callbacks)) || (count($callbacks) == 0))
            return null;
            
        krsort($callbacks);
        
        $retvals = array();
            
        foreach ($callbacks as $priority => $callbacklist)
        {
            if ((!$callbacklist) || (!is_array($callbacklist)) || (count($callbacklist) == 0))
                continue;
                
            if (!isset($retvals[$priority]))
                $retvals[$priority] = new \stdClass;
                
            foreach ($callbacklist as $callback)
            {
                if (!isset($retvals[$priority]->$callback))
                    $retvals[$priority]->$callback = null;
                    
                $retvals[$priority]->$callback = $callback->execute();
            }
        }
        
        if (count($retval) == 0)
            return null;
        
        return $retvals;
    }
    
    public function handle_signal($signal)
    {
        switch ($signal)
        {
            case SIGTERM:
                $this->handle_callbacks($signal);
                exit;
            case SIGKILL: // This should never happen, but...
                exit;
            default:
                $this->handle_callbacks($signal);
                break;
        }
    }
    
    public function register_signal($signal)
    {
        if (!is_array($this->signals))
            $this->init_signals();
        
        // Never register SIGKILL
        switch($signal)
        {
            case SIGKILL:
                return false;
        }
        
        pcntl_signal($signal, array(&$this, "handle_signal"));
        
        $this->signals[$signal] = round((microtime(true) * 1000));
        
        return true;
    }
    
    public function unregister_signal($signal)
    {
        if (!is_array($this->signals))
            $this->init_signals();
        
        // Never unregister SIGKILL
        switch ($signal)
        {
            case SIGKILL:
                return false;
        }
        
        pcntl_signal($signal, SIG_DFL);
        
        unset($this->signals[$signal]);
        
        return true;
    }
    
    public function ignore_signal($signal)
    {
        if (!is_array($this->signals))
            $this->init_signals();
        
        // Never ignore SIGTERM and SIGKILL
        switch ($signal)
        {
            case SIGTERM:
            case SIGKILL:
                return false;
        }
        
        pcntl_signal($signal, SIG_IGN);
        
        return true;
    }
    
    public function get_signal($signal = null)
    {
        if (!$signal)
            return $this->signals;
            
        if (isset($this->signals[$signal]))
            return $this->signals[$signal];
            
        return null;
    }
    
    public function init_signals()
    {
        if (!is_array($this->signals))
        {
            $this->signals = array();
            
            return false;
        }
        
        foreach ($this->signals as $signal => $timestamp)
        {
            $this->unregister_signal($signal);
        }
        
        $this->signals = array();
        
        return true;
    }
    
    public function register_callback($signal, $identifier, $priority = 0, Callback $callback = null)
    {
        if (!isset($this->callbacks[$signal]))
            $this->callbacks[$signal] = array();
            
        if (!isset($this->callbacks[$signal][$priority]))
            $this->callbacks[$signal][$priority] = new \stdClass;
            
        if (!isset($this->callbacks[$signal][$priority]->$identifier))
            $this->callbacks[$signal]->$identifier = $callback;
            
        return $this->callbacks[$signal];
    }
    
    public function unregister_callback($signal, $priority = null, $identifier = null)
    {
        if (!isset($this->callbacks[$signal]))
            return false;
            
        if (!$identifier)
        {
            if ($priority !== null)
            {
                if (!isset($this->callbacks[$signal][$priority]))
                    return false;
                    
                unset($this->callbacks[$signal][$priority]);
                
                return true;
            }
            
            unset($this->callbacks[$signal]);
            
            return true;
        }
        
        if ($priority === null)
            return false;
            
        if (!isset($this->callbacks[$signal][$priority]))
            return false;
            
        if (!isset($this->callbacks[$signal][$priority]->$identifier))
            return false;
            
        unset($this->callbacks[$signal][$priority]->$identifier);
        
        return true;
    }
    
    public function get_callback($signal = null, $priority = null, $identifier = null)
    {
        if ($signal === null)
            return $this->callbacks;
            
        if ($priority === null)
        {
            if (isset($this->callbacks[$signal]))
                return $this->callbacks[$signal];
            else
                return null;
        }
        
        if ($identifier === null)
        {
            if (!isset($this->callbacks[$signal]))
                return null;
                
            if (!isset($this->callbacks[$signal][$priority]))
                return null;
                
            return $this->callbacks[$signal][$priority];
        }
        
        if (!isset($this->callbacks[$signal]))
            return null;
            
        if (!isset($this->callbacks[$signal][$priority]))
            return null;
            
        if (!isset($this->callbacks[$signal][$priority]->$identifier))
            return null;
            
        return $this->callbacks[$signal][$priority]->$identifier;
    }
    
    public function init_callbacks()
    {
        $this->callbacks = array();
    }
    
    public function get_resource_usage($with_children = false)
    {
        return getrusage((($with_children) ? true : false));
    }
    
    public function trigger_signal($signal, &$errno = null, &$errstr = null)
    {
        $retval = posix_kill($this->pid, $signal);
        
        $errno = posix_errno();
        $errstr = posix_get_last_error();
        
        return $retval;
    }
    
    public function set_priority($priority)
    {
        if ($priority == $this->process_priority)
            return false;
            
        $this->process_priority = $priority;
        
        return pcntl_setpriority($priority, $this->pid);
    }
    
    public function set_async_signals($set = true)
    {
        return pcntl_async_signals($set);
    }
    
    public function set_alarm($seconds = null)
    {
        return pcntl_alarm($seconds);
    }
    
    public function unset_alarm()
    {
        return $this->set_alarm(0);
    }
    
    public function dispatch()
    {
        return pcntl_signal_dispatch();
    }
    
    public function usr1(&$errno = null, &$errstr = null)
    {
        return $this->trigger_signal(SIGUSR1, $errno, $errstr);
    }
    
    public function usr2(&$errno = null, &$errstr = null)
    {
        return $this->trigger_signal(SIGUSR2, $errno, $errstr);
    }
    
    public function hup(&$errno = null, &$errstr = null)
    {
        return $this->trigger_signal(SIGHUP, $errno, $errstr);
    }
    
    public function terminate(&$errno = null, &$errstr = null)
    {
        return $this->trigger_signal(SIGTERM, $errno, $errstr);
    }
    
    public function kill(&$errno = null, &$errstr = null)
    {
        return $this->trigger_signal(SIGKILL, $errno, $errstr);
    }
    
    public function fork($identifier, $wait = false, &$status = null)
    {
        $status = null;
        
        if (!is_array($this->child_pids))
            $this->child_pids = array();
            
        if (isset($this->child_pids[$identifier]))
        {
            $status = 1;
            
            return false;
        }
        
        $parent_pid = $this->pid;
        $fork_pid = pcntl_fork();
        
        if ($fork_pid == -1)
        {
            $status = -1;
            return false;
        }
        elseif ($fork_pid)
        {
            $this->child_pids[$identifier] = $fork_pid;
            
            if ($wait)
                pcntl_wait($status);
                
            return true;
        }
        else
        {            
            $status = 0;
            
            return $parent_pid;
        }
    }
    
    public function wait_child($identifier, $options = null, &$status = null)
    {
        if (!is_array($this->child_pids))
            return false;
            
        if (!isset($this->child_pids[$identifer]))
            return false;
            
        $child_pid = $this->child_pids[$identifier];
        
        return pcntl_waitpid($child_pid, $status, $options);
    }
    
    public function signal_child($signal, $identifier = null)
    {
        if (!is_array($this->child_pids))
            return false;
            
        foreach ($this->child_pids as $child_identifier => $child_pid)
        {
            if ($identifier)
            {
                if ($child_identifier != $identifier)
                    continue;
            }
            
            posix_kill($child_pid, $signal);
        }
        
        return true;
    }
    
    public function usr1_child($identifier = null)
    {
        return $this->signal_child(SIGUSR1, $identifier);
    }
    
    public function usr2_child($identifier = null)
    {
        return $this->signal_child(SIGUSR2, $identifier);
    }
    
    public function hup_child($identifier = null)
    {
        return $this->signal_child(SIGHUP, $identifier);
    }
    
    public function terminate_child($identifier = null)
    {
        return $this->signal_child(SIGTERM, $identifier);
    }
    
    public function kill_child($identifier = null)
    {
        return $this->signal_child(SIGKILL, $identifier);
    }
    
    public function write($str, $eol = "\n", $to_stderr = false)
    {
        $fd = @fopen("php://".(($to_stderr) ? "stderr" : "stdout"), "w");
        
        if (!is_resource($fd))
        {
            echo $str.$eol;
            
            return false;
        }
        
        @fwrite($fd, $str.$eol);
        @fclose($fd);
        
        return true;
    }
    
    public function read(&$str = null, $use_eol = null, $timeout = null)
    {
        $str = null;
        
        $fd = @fopen("php://stdin", "r");
        
        if (!is_resource($fd))
            return null;
            
        if ($timeout)
        {
            stream_set_blocking($fd, false);
            
            $timeout += time();
            
            while ($timeout > time())
            {
                $c = fgetc($fd);
                $str .= $c;
                
                if ($use_eol)
                {
                    if ($use_eol == $c)
                        break;
                }                
            }
        }
        else
        {
            stream_set_blocking($fd, true);
            
            if ($use_eol)
            {
                while ($c = fgetc($fd))
                {
                    $str .= $c;
                    
                    if ($c == $use_eol)
                        break;
                }
            }
            else
            {                    
                while (!feof($fd))
                {
                    $str .= fread($fd, 1024);
                }
            }        
        }
        
        @fclose($fd);
        
        return strlen($str);
    }
    
    public function input($output = null, $timeout = null, $use_stderr = false, $output_eol = "", $input_eol = "\n", &$bytecount = null)
    {
        $bytecount = null;
        $input = null;
        
        if ($output)
            $this->write($output, $output_eol, $use_stderr);
            
        $bytecount = $this->read($input, $input_eol, $timeout);
        
        return $input;
    }
    
    function __construct($process_title = null)
    {
        global $argc, $argv;
        
        $this->timestamp = round((microtime(true) * 1000));
        
        $this->pid = getmypid();
        $this->uid = getmyuid();
        $this->gid = getmygid();
        
        if ($process_title)
            $this->set_process_title($process_title);
        
        $this->process_priority = pcntl_getpriority($this->pid);
        
        $this->command = ((isset($argv[0])) ? $argv[0] : null);
        $this->arguments = array();
        $this->environment = getenv();
        $this->argumentcount = ($argc - 1);
        
        $this->pathname = dirname(realpath($this->command));
        $this->filename = basename(realpath($this->command));
        $this->lastmod = getlastmod();
        $this->inode =  getmyinode();
        
        if (is_array($argv))
        {
            for ($i = 1; $i < $argc; $i++)
            {
                $arg = $argv[$i];
                
                if (!isset($this->arguments[$arg]))
                    $this->arguments[$arg] = array();
                    
                array_push($this->arguments[$arg], $i);
            }
        }
        
        if (!$this->command)
            throw new \exception("Missing argument zero. Not on CLI?");
            
        if (!$this->argumentcount < 0)
            throw new \exception("Missing argument count. Not on CLI?");
                
        $this->init_signals();
        $this->init_callbacks();
    }
    
    function __destruct()
    {
        $this->kill_child();        
    }
}
