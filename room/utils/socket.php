<?php

declare(strict_types=1);

namespace socket;


define('socket\DOMAIN_UNIX', 1);
define('socket\DOMAIN_IPV4', 2);
define('socket\DOMAIN_IPV6', 10);

define('socket\TYPE_STREAM', 1);
define('socket\TYPE_DGRAM', 2);
define('socket\TYPE_RAW', 3);
define('socket\TYPE_RDM', 4);
define('socket\TYPE_SEQPACKET', 5);

define('socket\PROTOCOL_ICMP', getprotobyname('icmp'));
define('socket\PROTOCOL_TCP', getprotobyname('tcp'));
define('socket\PROTOCOL_UDP', getprotobyname('udp'));

define('socket\LEVEL_SOCKET', 1);

define('socket\OPTION_REUSEADDR', 2);
define('socket\OPTION_REUSEPORT', 15); // BSD only

define('socket\ERROR_AGAIN', 11);
define('socket\ERROR_BROKENPIPE', 32);
define('socket\ERROR_RESETPYBEER', 104);
