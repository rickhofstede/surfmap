<?php
    /*******************************
     # util.php [SURFmap]
     # Author: Rick Hofstede <r.j.hofstede@utwente.nl>
     # University of Twente, The Netherlands
     #
     # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
     *******************************/
    
    function fix_comma_separated_name ($name) {
        $result = $name;
        $comma_position = strpos($name, ",");
    
        if ($comma_position !== false) { # Comma found
            $result = substr($name, $comma_position + 2); // +2 to remove trailing white space
            $result .= " ".substr($name, 0, $comma_position);
        }
    
        return $result;
    }
    
    /*
     * Checks whether the specified IPv4 address belongs to the specified IP
     * address range (net).
     * Parameters:
     *      ip_address - IPv4 address in octet notation (e.g. '192.168.1.1')
     *      prefix - IPv4 subnet range, in nfdump filter notation
     */
    function ip_address_in_net ($ip_address, $prefix) {
         // http://pgregg.com/blog/2009/04/php-algorithms-determining-if-an-ip-is-within-a-specific-range/
        $ip_address_dec = ip2long($ip_address);
        
        // Since we use nfdump subnet notation, we need to make the subnet address complete
        $full_prefix = substr($prefix, 0, strpos($prefix, "/"));
        for ($i = 3 - substr_count($prefix, "."); $i > 0; $i--) {
            $full_prefix .= ".0";
        }
        $full_prefix_dec = ip2long($full_prefix);
        
        $net_mask = intval(substr($prefix, strpos($prefix, "/") + 1));
        $net_mask_dec = bindec(str_pad('', $net_mask, '1').str_pad('', 32 - $net_mask, '0'));
        
        return (($ip_address_dec & $net_mask_dec) == ($full_prefix_dec & $net_mask_dec));
    }
    
?>