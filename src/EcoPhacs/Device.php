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

use Norgul\Xmpp\XmppClient;
use Norgul\Xmpp\Options;

class Device
{
    private $did = null;
    private $class = null;
    private $name = null;
    private $nick = null;
    private $company = null;
    private $resource = null;
    private $atom = null;
    
    private $xmpp_client = null;
    private $xmpp_options = null;
    
    public const CLEANING_MODE_AUTO = 'auto';
    public const CLEANING_MODE_BORDER = 'border';
    public const CLEANING_MODE_SPOT = 'spot';
    public const CLEANING_MODE_STOP = 'stop';
    public const CLEANING_MODE_SINGLEROOM = 'singleroom';
    
    public const VACUUM_POWER_STANDARD = 'standard';
    public const VACUUM_POWER_STRONG = 'strong';
    
    public const VACUUM_STATUS_OFFLINE = 'offline';

    public const CHARGING_MODE_GO = 'go';
    public const CHARGING_MODE_GOING = 'Going';
    public const CHARGING_MODE_CHARGING = 'SlotCharging';
    public const CHARGING_MODE_IDLE = 'Idle';
    
    public const COMPONENT_SIDE_BRUSH = 'SideBrush';
    public const COMPONENT_BRUSH = 'Brush';
    public const COMPONENT_DUST_CASE_HEAP = 'DustCaseHeap';
        
    public function ping()
    {
        $this->xmpp_client->iq->pingTo($this->xmpp_options->fullJid(), $this->atom);
        
        return $this->xmpp_client->getResponse();
    }
    
    private function clean($mode = self::CLEANING_MODE_AUTO, $speed = self::VACUUM_POWER_STANDARD, $act = null)
    {
        $com = null;
        
        switch ($mode)
        {
            case self::CLEANING_MODE_AUTO:
            case self::CLEANING_MODE_BORDER:
            case self::CLEANING_MODE_SINGLEROOM:
            case self::CLEANING_MODE_SPOT:
            case self::CLEANING_MODE_STOP:
                $com = "<clean type='{$mode}' speed='{$speed}' act='{$act}'/>";
                break;
            default:
                return false;
        }

        $this->xmpp_client->iq->command($this->xmpp_options->fullJid(), $this->atom, "Clean", $com);
        
        return $this->xmpp_client->getResponse();
    }
    
    function __get($key)
    {
        if (!isset($this->$key))
            return null;
            
        if ($key == "xmpp")
            return null;
            
        return $this->$key;
    }
        
    function __construct(XmppClient $xmpp_client, Options $xmpp_options, $atom_domain, $did, $class, $name, $nick, $company, $resource)
    {
        $this->did = $did;
        $this->class = $class;
        $this->name = $name;
        $this->nick = $nick;
        $this->company = $company;
        $this->resource = $resource;
        $this->atom = $did."@".$class.".".$atom_domain."/".$resource;
        
        $this->xmpp_client = $xmpp_client;
        $this->xmpp_options = $xmpp_options;
    }
}
