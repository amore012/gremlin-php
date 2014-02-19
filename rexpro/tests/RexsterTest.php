<?php
namespace rexpro\tests;

require_once __DIR__.'/../Connection.php';

use \rexpro\Connection;
use \rexpro\Messages;
use \rexpro\Exceptions;
use \rexpro\Helper;


/**
 * Unit testing of Rexpro-php
 * 
 * @category DB
 * @package  Rexpro
 * @author   Dylan Millikin <dylan.millikin@brightzone.fr>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 apache2
 * @link     https://github.com/tinkerpop/rexster/wiki
 */
class RexsterTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Testing UUID
	 * 
	 * @return void
	 */
	public function testCreateUuid()
	{
	    $uuid1 = Helper::createUuid();
		$this->assertTrue(mb_strlen($uuid1, 'ISO-8859-1') == 36,'The generated UUID is not the correct length ');
		$this->assertTrue(count(str_split($uuid1,1)) == 36,'The generated UUID is not the correct length');
		
		$uuid = Helper::uuidToBin($uuid1);
		$this->assertTrue(mb_strlen($uuid, 'ISO-8859-1') == 16,'The conversion to bin of the UUID is not the correct length (16 bytes)');
		$this->assertTrue(count(str_split($uuid,1)) == 16,'The conversion to bin of the UUID is not the correct length (16 bytes)');
		//test that the bin format is correct for rexPro
		$this->assertEquals(bin2hex($uuid),str_replace('-','',trim($uuid1)),'The conversion to bin of the UUID is incorrect');
		
		$uuid = Helper::binToUuid($uuid);
		$this->assertTrue(mb_strlen($uuid, 'ISO-8859-1') == 36,'The conversion of bin UUID to UUID is not the correct length');
		$this->assertTrue(count(str_split($uuid,1)) == 36,'The conversion of bin UUID to UUID is not the correct length');
		$this->assertEquals($uuid,$uuid1,'UUID before and after convertion do not match');
	}
	
	/**
	 * Testing binary conversion TO
	 * 
	 * @return void
	 */
	public function testConvertIntTo32Bit()
	{
		$converted = Helper::convertIntTo32Bit(84);
		$this->assertEquals(mb_strlen($converted, 'ISO-8859-1'),4,'The converted int is not the correct byte length (4 bytes)'); //should be 32 bits / 4 bytes
		$this->assertEquals(bin2hex($converted),'00000054','The converted int is incorrect');
		
		$converted = Helper::convertIntTo32Bit(9999);
		$this->assertEquals(mb_strlen($converted, 'ISO-8859-1'),4,'The converted int is not the correct byte length (4 bytes)'); //should be 32 bits / 4 bytes
		$this->assertEquals(bin2hex($converted),'0000270f','The converted int is incorrect');
		
		$converted = Helper::convertIntTo32Bit(10000000000);
		$this->assertEquals(mb_strlen($converted, 'ISO-8859-1'),4,'The converted int is not the correct byte length (4 bytes)'); //should be 32 bits / 4 bytes
		$this->assertNotEquals(bin2hex($converted),'2540BE400','The converted int is incorrect. ints above 4 bytes should have the extra bytes truncated'); // hex for 10000000000
		//the extra 3 bits should be taken off the begining of binary data. This test checks this
		$this->assertEquals(bin2hex($converted),'540be400','The converted int is incorrect. ints above 4 bytes should have the extra bytes truncated');
		
		
	}
	
	/**
	 * Testing binary conversion FROM
	 * 
	 * @return void
	 */
	public function testConvertIntFrom32Bit()
	{
		$converted = Helper::convertIntFrom32Bit(Helper::convertIntTo32Bit(84));
		$this->assertEquals($converted,84,'The conversion of 32bit int to int is incorrect');

		$converted = Helper::convertIntFrom32Bit(Helper::convertIntTo32Bit(9999));
		$this->assertEquals($converted,9999,'The conversion of 32bit int to int is incorrect');

		$converted = Helper::convertIntFrom32Bit(Helper::convertIntTo32Bit(10000000000));
		$this->assertEquals($converted,1410065408,'The conversion of 32bit int to int is incorrect. Bit truncating issue'); //bit truncating check
	}
	
	/**
	 * Testing Connection
	 * 
	 * @return void
	 */
	public function testConnectSuccess()
	{
		$message = new Messages;
		
		$db = new Connection;
		$result = $db->open('localhost','tinkergraph','test','ghJK5-hG');
		
		$this->assertNotEquals($result,false,'Failed to connect with no params');
		$this->assertTrue($db->response[2] == 2,'Result for session connection (without params) is not a session start response packet');//check it's a session start server packet
		
		$db = new Connection;
		$result = $db->open('localhost','tinkergraph','test','ghJK5-hG');
		$this->assertNotEquals($result,false,'Failed to connect with "localhost"');
		$this->assertTrue($db->response[2] == 2,'Result for session connection (with localhost) is not a session start response packet');//check it's a session start server packet
		
		$db = new Connection;
		$result = $db->open('localhost','neo4jsample','test','ghJK5-hG');
		$this->assertNotEquals($result,false,'Failed to connect with localhost and neo4jsample graph');
		$this->assertTrue($db->response[2] == 2,'Result for session connection (with localhost and neo4jsample graph) is not a session start response packet');//check it's a session start server packet
	}
	
	/**
	 * Testing connection errors
	 * 
	 * @return void
	 */
	public function testConnectErrors()
	{	
		$db = new Connection;
		$db->timeout = 0.5;
		$result = $db->open('unknownhost');
		$this->assertEquals($result,false,'Connecting to an unknown host does not throw an error');
		
		$this->assertNotEquals($db->error,null,'Error object was not properly populated for unknown host');
		$this->assertTrue($db->error instanceof Exceptions,'Error object is not an Exceptions Object for unknown host');
		$this->assertFalse(null === $db->error->code,'Error object does not contain an error code for unknown host');
		$this->assertFalse(null === $db->error->description,'Error object does not contain an error description for unknown host');
		
		$db = new Connection;
		$db->timeout = 0.5;
		$result = $db->open('localhost:8787');
		$this->assertEquals($result,false,'Connecting to the wrong port for localhost does not throw an error');
		
		$this->assertTrue($db->error instanceof Exceptions,'Error object is not an Exceptions Object for unknown port');
		$this->assertFalse(null === $db->error->code,'Error object does not contain an error code for unknown port');
		$this->assertFalse(null === $db->error->description,'Error object does not contain an error description for unknown port');
		
		$db = new Connection;
		$result = $db->open('localhost','doesnt exist','test','ghJK5-hG');
		$this->assertEquals($result,false,'Loading a non-existing graph doesn\'t throw error');
		
		$this->assertTrue($db->error instanceof Exceptions,'Error object is not an Exceptions Object for unknown graph');
		$this->assertFalse(null === $db->error->code,'Error object does not contain an error code for unknown graph');
		$this->assertFalse(null === $db->error->description,'Error object does not contain an error description for unknown graph');
		
		$db = new Connection;
		$result = $db->open('localhost','doesnt exist','test','g-hG');
		$this->assertEquals($result,false,'Loading a non-existing user doesn\'t throw error');
		
		$this->assertTrue($db->error instanceof Exceptions,'Error object is not an Exceptions Object for unknown user');
		$this->assertFalse(null === $db->error->code,'Error object does not contain an error code for unknown user');
		$this->assertFalse(null === $db->error->description,'Error object does not contain an error description for unknown user');
	}
	
	/**
	 * Testing connection close
	 * 
	 * @return void
	 */
	public function testConnectCloseSuccess()
	{
		//do all connection checks
		$db = new Connection;
		$result = $db->open('localhost','tinkergraph','test','ghJK5-hG');

		//check disconnection
		$response = $db->close();
		
		$this->assertNotEquals($response,false,'Closing connection on empty param connect creates an Error');
		$this->assertFalse($db->isConnected(),'Despite not throwing errors, Socket connection is not established');
		$this->assertTrue($db->response[2] == 2,'Response packet for closing session is not the proper type. (Maybe it\'s an error)');//check it's a session stop server packet
	}	
	
	/**
	 * Testing Script run against DB
	 * 
	 * @return void
	 */
	public function testRunScript()
	{
		$db = new Connection;
		$message = $db->open('localhost:8184','tinkergraph','test','ghJK5-hG');
		
		$db->script = '5+5';
		$result = $db->runScript();

		$this->assertNotEquals($result,false,'Script request throws an error');
		$this->assertTrue($db->response[2] == 5,'Script response message is not the right type. (Maybe it\'s an error)');//check it's a session script reply
		
		$db->script = 'g.v(2)';
		$result = $db->runScript();
		
		$this->assertNotEquals($result,false,'Script request throws an error');
		$this->assertTrue($db->response[2] == 5,'Script response message is not the right type. (Maybe it\'s an error)');//check it's a session script reply
		
		//check disconnection
		$message = $db->close();
		$this->assertNotEquals($message,false,'Closing connection on script creates an Error');
		$this->assertFalse($db->isConnected(),'Despite not throwing errors, Socket connection is not established');
		$this->assertTrue($db->response[2] == 2,'Response packet for closing session is not the proper type. (Maybe it\'s an error)');//check it's a session stop server packet
	}	
	
	/**
	 * Testing Script run with bindings
	 * 
	 * @return void
	 */
	public function testRunScriptWithBindings()
	{
		$db = new Connection;
		$message = $db->open('localhost:8184','tinkergraph','test','ghJK5-hG');
		$this->assertNotEquals($message,false);
		
		$db->script = 'g.v(CUSTO_BINDING)';
		$db->bindValue('CUSTO_BINDING',2);
		$result = $db->runScript();
		//print_r($db->error);
		$this->assertNotEquals($result,false,'Running a script with bindings produced an error');
		$this->assertTrue($db->response[2] == 5,'Script response message is not the right type. (Maybe it\'s an error)');//check it's a session script reply
		
		//check disconnection
		$message = $db->close();
		$this->assertNotEquals($message,false,'Disconnecting from a session where bindings were used created an error');
		$this->assertTrue($db->response[2] == 2,'Response packet for closing session with bindings is not the proper type. (Maybe it\'s an error)');//check it's a session stop server packet
	}
	
	/**
	 * Testing Script run without isolation
	 * 
	 * @return void
	 */
	public function testRunScriptWithoutIsolation()
	{
		$db = new Connection;
		$message = $db->open('localhost:8184','tinkergraph','test','ghJK5-hG');
		$this->assertNotEquals($message,false);
		
		$db->script = 'g.v(CUSTO_BINDING)';
		$db->bindValue('CUSTO_BINDING',2);
		$result = $db->runScript(true,false);
		
		$this->assertNotEquals($result,false,'There was an error when running a script with bindings in non-isolated mode');
		$this->assertTrue($db->response[2] == 5,'Script response message is not the right type. (Maybe it\'s an error)');//check it's a session script reply
	
		$db->script = 'g.v(CUSTO_BINDING)';
		$result = $db->runScript(true,false); // would return an error if isolate was true
		$this->assertNotEquals($result,false,'Script created an error when using bindings that were set in a previous script call in non-isolation mode' );
		$this->assertTrue($db->response[2] == 5,'Script response message is not the right type. (Maybe it\'s an error)');//check it's a session script reply
		
		/*$db->script = 'g.v(CUSTO_BINDING)';
		$result = $db->runScript(); // would return an error if isolate was true
		print_r($result);
		$this->assertEquals($result,false,'No error occured when trying to use a binding from a previous message in isolated mode' );
		$this->assertTrue($db->response[2] == 5,'Script response message is not the right type. (Maybe it\'s an error)');//check it's a session script reply
		*/
		
		//check disconnection
		$message = $db->close();
		$this->assertNotEquals($message,false,'Disconnecting from a session where bindings were used in consequent non-isolated scripts created an error');
		$this->assertTrue($db->response[2] == 2,'Response packet for closing session with bindings used in consequent non-isolated scripts is not the proper type. (Maybe it\'s an error)');//check it's a session stop server packet
	}	
	
	/**
	 * Testing sessionless script run
	 * 
	 * @return void
	 */
	public function testRunSessionlessScript()
	{
		$db = new Connection;
		$message = $db->open('localhost:8184','tinkergraph','test','ghJK5-hG');
		
		$db->script = 'g.v(2).map()';
		$db->graph = 'tinkergraph';
		$result = $db->runScript(false); //need to provide graph
		$this->assertNotEquals($result,false, 'Running a sessionless script returned an error');
		$this->assertTrue($db->response[2] == 5,'Script response message is not the right type. (Maybe it\'s an error)');//check it's a session script reply
		
		//check disconnection
		$message = $db->close();
		$this->assertNotEquals($message,false,'Disconnecting from a session after sessionless script created an error');
		$this->assertTrue($db->response[2] == 2,'Response packet for closing session after sessionless script is not the proper type. (Maybe it\'s an error)');//check it's a session stop server packet		
	}
	
	/**
	 * Testing transactions
	 * 
	 * @return void
	 */
	public function testTransactions()
	{
		$db = new Connection;
		$message = $db->open('localhost:8184','neo4jsample','test','ghJK5-hG');
		$this->assertNotEquals($message,false);

		$db->script = 'g.V';
		$elementCount = count($db->runScript());
		
		$db->transactionStart();

		$db->script = 'g.addVertex([name:"michael"])';
		$result = $db->runScript();
		$this->assertNotEquals($result,false,'Script request throws an error in transaction mode');
		$this->assertTrue($db->response[2] == 5,'Script response message in transaction mode is not the right type. (Maybe it\'s an error)');//check it's a session script reply
		
		$db->transactionStop(false);
		
		$db->script = 'g.V';
		$elementCount2 = count($db->runScript());
		$this->AssertEquals($elementCount,$elementCount2,'Transaction rollback didn\'t work');
		
		$db->transactionStart();

		$db->script = 'g.addVertex([name:"michael"])';
		$result = $db->runScript();
		$this->assertNotEquals($result,false,'Script request throws an error in transaction mode');
		$this->assertTrue($db->response[2] == 5,'Script response message in transaction mode is not the right type. (Maybe it\'s an error)');//check it's a session script reply
		
		$db->transactionStop(true);
		
		$db->script = 'g.V';
		$elementCount2 = count($db->runScript());
		$this->AssertEquals($elementCount+1,$elementCount2,'Transaction submition didn\'t work');
		
	}
	
	
}
