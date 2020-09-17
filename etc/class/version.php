<?php
/**
 * Version information for LAN Ahead.
 * @author misterhaan
 */
class Version {
	/**
	 * Database structure (tables and routines) version.  Changing this triggers
	 * the setup script in upgrade mode.
	 * @var integer
	 */
	const Structure = StructureVersion::Empty;
}

/**
 * List of structure versions for LAN Ahead.  New versions should be
 * added at the top and use the next integer value.  Be sure to update
 * InstallDatabase() and UpgradeDatabaseStructure() in setup.php.
 * @author misterhaan
 */
class StructureVersion {
	const Empty = 0;
}
