# EcoPhacs is a PHP client library for the Ecovacs API

[![License](https://poser.pugx.org/welterrocks/ecophacs/license)](https://packagist.org/packages/welterrocks/ecophacs)
[![Total Downloads](https://poser.pugx.org/welterrocks/ecophacs/downloads)](https://packagist.org/packages/welterrocks/ecophacs)
[![Latest Stable Version](https://poser.pugx.org/welterrocks/ecophacs/v/stable)](https://packagist.org/packages/welterrocks/ecophacs)
[![Latest Unstable Version](https://poser.pugx.org/welterrocks/ecophacs/v/unstable)](https://packagist.org/packages/welterrocks/ecophacs)
[![Linux Build](https://travis-ci.org/welterrocks/ecophacs.svg?branch=main)](https://travis-ci.org/welterrocks/ecophacs)
[![Windows Build](https://ci.appveyor.com/api/projects/status/github/welterrocks/ecophacs)](https://ci.appveyor.com/project/WelterRocks/ecophacs)

This library uses PHP to connect to the Ecovacs API and let you control your
Ecovacs based devices, like Deebot for example. You need an Ecovacs cloud
account and a password to login or a local server like [Bumper](https://github.com/bmartin5692/bumper). 
The library shows and let you control the supported and registered devices, 
linked to your account. EcoPhacs has been written in PHP and is an alternative
to the [Sucks](https://github.com/wpietri/sucks) project.

# News
- **2020-10-21** Added bumper compat.
Now, it is possible to use EcoPhacs with bumper. Added some compatibility code to make EcoPhacs work
with the bumper server project.
- **2020-10-17** EcoPhacs-MQTT release.
The first beta version of the EcoPhacs-MQTT daemon has been released. Now, it is possible to connect
your Ecovacs Deebot directly to a MQTT broker, which is e.g. useful to control and supervise your Ecovacs
robot devices with your favorite IOT controllers, like OpenHAB. The EcoPhacs-MQTT daemon automatically 
requests device status updates and post them through the given MQTT topic. Currently the script doenst
survive a broker restart, because of some unexpected behaviour of the third party mosquitto lib. But this
will be fixed in the next few days.
- **2020-10-16** EcoPhacs-Daemon and EcoPhacs-Client released.
If you would like to have fast access to your bots and immidiate command reactions and responses,
then this new daemon/client combination is probably what you are searching for. You need two FIFOs 
in /run, named `ecophacs-in.fifo` and `ecophacs-out.fifo`, which can be accessed by the 
`EcoPhacs-Daemon.php` while running. The requirement to run the daemon is to create a config
file, using `EcoPhacs-Configure.php`. After the daemon has started, you can use EcoPhacs-Client.php
to send commands, which your bot immidiatly executes.

# Installation requirements

Project requirements are given in `composer.json` (
[Composer website](https://getcomposer.org)):

You can use this library in your project by running:

```
composer require welterrocks/ecophacs
```

or just clone it from GitHub:

```
git clone https://github.com/WelterRocks/ecophacs
```

After you got your copy of ecophacs, change to the installation directory
and run the following command, if you want to install ecophacs to your
system:

```
sudo ./install.sh
```

Just wait for the script to finish the installation. To create a config
file, just type:

```
sudo EcoPhacs-Configure.php
```

After that, copy ~/.ecophacsrc to /etc/ecophacs/ecophacs.conf:

```
sudo cp ~/.ecophacsrc /etc/ecophacs/ecophacs.conf
```

Now you can start the daemon:

```
sudo EcoPhacs-Daemon.php start
```

If you would like to start the daemon at system start-up, type:

```
systemctl enable ecophacs-daemon
```

to see, whats going on, tail your syslog:

```
sudo tail -f /var/log/syslog
```

Now, you can start controlling your bots:

```
EcoPhacs-Client.php devicelist
```

This will give you the list of registered devices. Choose one,
e.g. E0000111122223333344444, clean for 10 seconds and than go
back to the charger:

```
EcoPhacs-Client.php --device-id E0000111122223333344444 auto --wait 10 charge
```

If you want to use the MQTT version, remember to stop the Daemon, because
synchronous running is currently not supported. If you use systemd, it will 
automatically take care of stopping the other daemon. You can run the MQTT
daemon with:

```
EcoPhacse-MQTT.php start
```

But remember, to configure your /etc/ecophacs/ecophacs.conf with `EcoPhacs-Configure.php`
to set up your MQTT credentials. Also, TLS is currently not supported by the
`EcoPhacs-MQTT.php` tool.

## Bumper usage
If you want to use EcoPhacs with bumper, add the following options to `EcoPhacs-Daemon.php`
or `EcoPhacs-MQTT.php`, to enable to bumper compatibility mode in EcoPhacs:

```
EcoPhacs-{Daemon|MQTT}.php --bump-api /path/to/bumper/certs/bumper.crt --dry-login --api-urls "https://[IP-OF-BUMPER-SERVER]/v1/private/%country%/%app_language%/%device_id%/%app_code%/%app_version%/%app_channel%/%device_type%" "https://[IP-OF-BUMPER-SERVER]/api/users/user.do" --is-bumper-server start|foreground
```

# Library usage
## General information
This library is in an early development stage. You can use this library
with the ecovacs cloud, to control your registered devices. Also it has
been tested with [Bumper](https://github.com/bmartin5692/bumper), which
is a great project by [Brian Martin](https://github.com/bmartin5692), giving you a local server for your bots.
Use this lib with care and do NOT use it for production environments for now,
because there is too much testing to do...Hope I can get you to give it a try.

## TODO's

- **testing** - writing some testing routines for packaging
currently there is only one working Example.php and no tests
