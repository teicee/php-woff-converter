<?php
/**
 * WoffConverter - PHP class to convert a WOFF font file into a TTF/OTF font file.
 *
 * @see         https://github.com/teicee/php-woff-converter
 * @author      Grégory Marigot (téïcée) <gmarigot@teicee.com> (@proxyconcept)
 * @license     http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @package     teicee/woff-converter
 */
namespace TIC\WoffConverter;

/**
 * Utility class to convert font file from WOFF to TTF.
 */
class Woff
{
	/**
	 * @var     boolean     True to enable debug informations on stdout.
	 */
	public static $debug = false;

	/**
	 * @const   array       Entries name & format from a WOFF header.
	 */
	const WOFF_Header = array(
		'signature'       => "N",  // 0x774F4646 'wOFF'
		'flavor'          => "N",  // The "sfnt version" of the original file: 0x00010000 for TrueType flavored fonts or 0x4F54544F 'OTTO' for CFF flavored fonts.
		'length'          => "N",  // Total size of the WOFF file.
		'numTables'       => "n",  // Number of entries in directory of font tables.
		'reserved'        => "n",  // Reserved, must be set to zero.
		'totalSfntSize'   => "N",  // Total size needed for the uncompressed font data, including the sfnt header, directory, and tables.
		'majorVersion'    => "n",  // Major version of the WOFF font, not necessarily the major version of the original sfnt font.
		'minorVersion'    => "n",  // Minor version of the WOFF font, not necessarily the minor version of the original sfnt font.
		'metaOffset'      => "N",  // Offset to metadata block, from beginning of WOFF file; zero if no metadata block is present.
		'metaLength'      => "N",  // Length of compressed metadata block; zero if no metadata block is present.
		'metaOrigLength'  => "N",  // Uncompressed size of metadata block; zero if no metadata block is present.
		'privOffset'      => "N",  // Offset to protected data block, from beginning of WOFF file; zero if no protected data block is present.
		'privLength'      => "N",  // Length of protected data block; zero if no protected data block is present.
	);
	const WOFF_HeaderSize   = 44;
	const TTF_HeaderSize    = 12;

	/**
	 * @const   array       Entries name & format from a WOFF table directory.
	 */
	const WOFF_TableDirEntry = array(
		'tag'             => "N",  // 4-byte sfnt table identifier.
		'offset'          => "N",  // Offset to the data, from beginning of WOFF file.
		'compLength'      => "N",  // Length of the compressed data, excluding padding.
		'origLength'      => "N",  // Length of the uncompressed table, excluding padding.
		'origChecksum'    => "N",  // Checksum of the uncompressed table.
	);
	const WOFF_TableDirSize = 20;
	const TTF_TableDirSize  = 16;


	/**
	 * Display debug message.
	 *
	 * @param   string      $label      Debug information
	 * @param   mixed       $data       Variable to dump (print_r)
	 */
	protected static function debug(string $label, $data = null)
	{
		\printf("=== %s: %s\n", $label, \print_r($data, true));
	}

	/**
	 * Generate error message.
	 *
	 * @param   string      $method     Class method name
	 * @param   string      $message    Error message
	 */
	protected static function error(string $method, string $message)
	{
		if (self::$debug) \printf("*** [%s] %s!\n", $method, $message);
		throw new \Exception("[WoffConverter] $message");
	}


//  -------------------------------------------------------------------- WOFF Methods


	/**
	 * Read the header from a WOFF file.
	 *
	 * @param   resource    $fh         Input file handle
	 * @return  array                   List of header entries
	 */
	protected static function woffReadHeader($fh): array
	{
		$format = array();
		foreach (self::WOFF_Header as $name => $code) $format[] = $code . $name;
		
		$header = \unpack(\implode('/', $format), \fread($fh, self::WOFF_HeaderSize));
		if (self::$debug) self::debug("WOFF Header", $header);
		
		if ($header['signature'] !== 0x774F4646)  // wOFF
			self::error(__METHOD__, "Bad signature: input file is not a valid WOFF font");
		
		if ($header['reserved'] !== 0)
			self::error(__METHOD__, "Invalid header: reserved field should be 0");
		
		switch ($header['flavor']) {
			case 0x00010000: $header['type'] = 'ttf'; break;
			case 0x4F54544F: $header['type'] = 'cff'; break;
			default:         $header['type'] = 'xxx'; break;
		}
		if (self::$debug) self::debug("WOFF Flavor", $header['type']);
		
		return $header;
	}

	/**
	 * Read the table directory from a WOFF file.
	 *
	 * @param   resource    $fh         Input file handle
	 * @param   int         $numTables  Number of font tables to read
	 * @return  array                   List of table directory entries
	 */
	protected static function woffReadTableDir($fh, int $numTables): array
	{
		$format = array();
		foreach (self::WOFF_TableDirEntry as $name => $code) $format[] = $code . $name;
		
		$entries = array();
		for ($n = 0; $n < $numTables; $n++) {
			$entries[] = \unpack(\implode('/', $format), \fread($fh, self::WOFF_TableDirSize));
		}
		
		if (self::$debug) self::debug("WOFF Table directory", $entries);
		return $entries;
	}

	/**
	 * Read the font tables from a WOFF file.
	 *
	 * @param   resource    $fh         Input file handle
	 * @param   array       $entries    List of table directory entries
	 * @return  array                   List of font data tables
	 */
	protected static function woffReadFontTables($fh, array &$entries): array
	{
		$tables = array();
		foreach ($entries as $n => &$entry) {
			$origPos = \ftell($fh);
			\fseek($fh, $entry['offset']); // or error
			
			if ($entry['compLength'] === $entry['origLength']) {
				$tables[$n] = \fread($fh, $entry['compLength']);
			} else {
				$tables[$n] = \gzuncompress(\fread($fh, $entry['compLength']));
			}
			\fseek($fh, $origPos);
			
			$entry['dataLength'] = \strlen($tables[$n]);
		}
		if (self::$debug) self::debug("WOFF Font tables", \array_map('strlen', $tables));
		return $tables;
	}

	/**
	 * Read the private data from a WOFF file.
	 *
	 * @param   resource    $fh         Input file handle
	 * @param   int         $offset     Start position
	 * @param   int         $length     Data length to read
	 * @return  string                  Private data content
	 */
	protected static function woffReadPrivData($fh, int $offset, int $length): string
	{
		if (! $length) return '';
		
		$origPos = \ftell($fh);
		\fseek($fh, $offset);
		$privData = \fread($fh, $length);
		\fseek($fh, $origPos);
		
		if (self::$debug) self::debug("WOFF protected data", $privData);
		return $privData;
	}

	/**
	 * Read all data and informations from a WOFF file.
	 * @see https://www.w3.org/TR/WOFF/
	 *
	 * @param   string      $path       Path for the input file
	 * @return  array                   Font data (with keys 'headers', 'entries' & 'fontTbl')
	 */
	protected static function woffReader(string $path): array
	{
		if (! $fh = \fopen($path, 'rb'))
			self::error(__METHOD__, "Wrong input: couldn't open WOFF file '$path'");
		
		$headers = self::woffReadHeader($fh);
		$entries = self::woffReadTableDir($fh, $headers['numTables']);
		$fontTbl = self::woffReadFontTables($fh, $entries);
#		$private = self::woffReadPrivData($fh, $headers['privOffset'], $headers['privLength']);
		\fclose($fh);
		
		return array(
			'headers' => $headers,
			'entries' => $entries,
			'fontTbl' => $fontTbl,
#			'private' => $private,
		);
	}


//  -------------------------------------------------------------------- TTF Methods


	/**
	 * Write the header to a TTF file.
	 *
	 * @param   resource    $fh         Output file handle
	 * @param   array       $headers    List of header entries
	 * @return  int                     Total bytes written
	 */
	protected static function ttfWriteHeader($fh, array $headers): int
	{
		$entrySelector = \floor(\log($headers['numTables'], 2));
		$searchRange   = \pow(2, $entrySelector) * 16;
		$rangeShift    = $headers['numTables'] * 16 - $searchRange;
		if (self::$debug) {
			self::debug("TTF searchRange",   $searchRange);
			self::debug("TTF entrySelector", $entrySelector);
			self::debug("TTF rangeShift",    $rangeShift);
		}
		
		$c = (int)\fwrite($fh, \pack('Nnnnn',
			$headers['flavor'],
			$headers['numTables'],
			$searchRange,
			$entrySelector,
			$rangeShift
		));
		
		if ($c === self::TTF_HeaderSize) return $c;
		self::error(__METHOD__, "TTF output error: wrong header length ($c)");
	}

	/**
	 * Write the table directory to a TTF file.
	 *
	 * @param   resource    $fh         Output file handle
	 * @param   array       $entries    List of table directory entries
	 * @return  int                     Total bytes written
	 */
	protected static function ttfWriteTableDir($fh, array $entries): int
	{
		$dataOffset = self::TTF_HeaderSize + self::TTF_TableDirSize * \count($entries);
		
		$c = 0;
		foreach ($entries as $n => $entry) {
			$c+= (int)\fwrite($fh, \pack('NNNN',
				$entry['tag'],
				$entry['origChecksum'],
				$dataOffset,
				$entry['dataLength']
			));
			$mod4 = $entry['dataLength'] % 4;
			$dataOffset+= $entry['dataLength'] + (($mod4) ? (4 - $mod4) : 0);
		}
		
		if ($c === self::TTF_TableDirSize * \count($entries)) return $c;
		self::error(__METHOD__, "TTF output error: wrong table directory length ($c)");
	}

	/**
	 * Write the font tables to a TTF file.
	 *
	 * @param   resource    $fh         Output file handle
	 * @param   array       $entries    List of table directory entries
	 * @param   array       $tables     List of font data tables
	 * @return  int                     Total bytes written
	 */
	protected static function ttfWriteFontTables($fh, array $entries, array &$tables): int
	{
		$t = 0;
		foreach ($entries as $n => $entry) {
			$c = (int)\fwrite($fh, $tables[$n], $entry['dataLength']);
			$s = $entry['dataLength'];
			
			if ($mod4 = $entry['dataLength'] % 4) {
				$c+= (int)\fwrite($fh, \pack("x".(4 - $mod4)));
				$s+= (4 - $mod4);
			}
			
			if ($c !== $s) self::error(__METHOD__, "TTF output error: failed to write font table ($n: $c/$s)");
			$t+= $c;
		}
		return $t;
	}

	/**
	 * Write all data and informations to a TTF file.
	 * @see https://docs.microsoft.com/fr-fr/typography/opentype/spec/otff
	 *
	 * @param   string      $path       Path for the output file
	 * @param   array       $font       Font data (with keys 'headers', 'entries' & 'fontTbl')
	 * @return  int                     Total bytes written
	 */
	protected static function ttfWriter(string $path, array $font): int
	{
		if (! $fh = \fopen($path, 'wb'))
			self::error(__METHOD__, "Wrong output: couldn't open TTF file '$path'");
		
		$c = 0;
		$c+= self::ttfWriteHeader($fh, $font['headers']);
		$c+= self::ttfWriteTableDir($fh, $font['entries']);
		$c+= self::ttfWriteFontTables($fh, $font['entries'], $font['fontTbl']);
		
		\fclose($fh);
		return $c;
	}


//  -------------------------------------------------------------------- Public Methods


	/**
	 * Convert a WOFF file to a TTF file.
	 *
	 * @param   string      $woffFile   Path for the source file
	 * @param   string      $ttfFile    Path for the generated file
	 * @return  int                     Total bytes written
	 */
	public static function toTTF(string $woffFile, string $ttfFile = null): int
	{
		if (null === $ttfFile) $ttfFile = \sprintf('%s/%s.ttf', \dirname($woffFile), \basename($woffFile, '.woff'));
		
		return self::ttfWriter($ttfFile, self::woffReader($woffFile));
	}

}
