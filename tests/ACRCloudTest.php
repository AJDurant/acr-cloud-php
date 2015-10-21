<?php

namespace AJDurant\ACRCloudTest;

use \AJDurant\ACRCloud\ACRCloud;

class ACRCloudTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    private function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function testIdentifyNormal()
    {
        $acr = $this->getMockBuilder('\AJDurant\ACRCloud\ACRCloud')
            ->setConstructorArgs(['key', 'secret'])
            ->setMethods(['getWavData', 'apiPost'])
            ->getMock();

        $acr->expects($this->once())
            ->method('getWavData')
            ->with(
                $this->equalTo('testfile'),
                $this->equalTo(5),
                $this->equalTo(20)
            )->will($this->returnValue('wavdata'));

        $acr->expects($this->once())
            ->method('apiPost')
            ->with(
                $this->equalTo('wavdata')
            )->will($this->returnValue('{"status":{"msg":"Testing JSON","code":1234,"version":"1.0"}}'));

        $data = $acr->identify('testfile');

        $dataAssert = [
            'status' => [
                'msg' => 'Testing JSON',
                'code' => 1234,
                'version'=> '1.0'
            ]
        ];

        $this->assertEquals($dataAssert, $data);
    }

    public function testIdentifyParams()
    {
        $acr = $this->getMockBuilder('\AJDurant\ACRCloud\ACRCloud')
            ->setConstructorArgs(['key', 'secret'])
            ->setMethods(['getWavData', 'apiPost'])
            ->getMock();

        $acr->expects($this->once())
            ->method('getWavData')
            ->with(
                $this->equalTo('testfile'),
                $this->equalTo(10),
                $this->equalTo(30)
            )->will($this->returnValue('wavdata'));

        $acr->expects($this->once())
            ->method('apiPost')
            ->with(
                $this->equalTo('wavdata')
            )->will($this->returnValue('{"status":{"msg":"Testing JSON","code":1234,"version":"1.0"}}'));

        $data = $acr->identify('testfile', 10, 30);

        $dataAssert = [
            'status' => [
                'msg' => 'Testing JSON',
                'code' => 1234,
                'version'=> '1.0'
            ]
        ];

        $this->assertEquals($dataAssert, $data);
    }

}
