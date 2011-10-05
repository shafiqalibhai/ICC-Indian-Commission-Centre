<?php
/**
 * J!Analytics - Google Analytics Code module for Joomla v1.5
 *
 * @version		$Id: urchin.php 22 2009-03-05 08:41:13Z mofodojodino $
 * @author		Dean Tedesco <dino@tedesco.net.au>
 * @link		http://www.tedesco.net.au
 * @copyright 	Copyright (C) 2008 Dean Tedesco. All rights reserved.
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * This file is part of J!Analytics.
 * 
 * J!Analytics is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * J!Analytics is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with J!Analytics.  If not, see <http://www.gnu.org/licenses/>.
 */

defined('_JEXEC') or die('Restricted access');
?>
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
try {
_uacct = "<?php echo $analyticsId; ?>";
urchinTracker();
} catch(err) {}</script>