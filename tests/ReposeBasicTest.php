<?php

require_once('AbstractReposeTest.php');

class ReposeBasicTest extends AbstractReposeTest {

    /**
     * Setup
     *
     * Called prior to each and every test.
     */
    public function setUp() {
    }

    /**
     * Test simple identity
     * Does a simple check to make certain that the same object will
     * compare with === . If this test fails things will not go well
     * trying to use Repose.
     */
    public function testSimpleIdentity() {
        $this->loadClass('sample_User');
        $userBeau = new sample_User('beau');
        $userMapOne = array('user' => $userBeau);
        $userMapTwo = array('user' => $userBeau);
        $this->assertTrue($userMapOne['user'] === $userMapTwo['user']);
    }

    /**
     * Model test without persistence.
     */
    public function testSimpleModelUsageWithoutPersistence() {

        $this->loadClass('sample_User');
        $this->loadClass('sample_Project');
        $this->loadClass('sample_Bug');

        $userBeau = new sample_User('beau');
        $userJosh = new sample_User('josh');

        $project = new sample_Project('Sample Project', $userBeau);

        $bug = new sample_Bug(
            $project,
            'Something is broken',
            'Click http://example.com/ to test!',
            $userJosh, // Reporter
            $userBeau // Owner
        );

        $this->assertEquals('Something is broken', $bug->title);
        $this->assertEquals('Click http://example.com/ to test!', $bug->body);

        $this->assertEquals('josh', $bug->reporter->name, 'Reporter');
        $this->assertEquals('beau', $bug->owner->name, 'Owner');

        $this->assertEquals('Sample Project', $bug->project->name, 'Bug\'s Project\'s name does not match');
        $this->assertEquals('beau', $bug->project->manager->name, 'Manager');

        $this->assertEquals('Sample Project', $project->name);
        $this->assertEquals('beau', $project->manager->name, 'Manager');

        $this->assertEquals('beau', $userBeau->name);
        $this->assertEquals('josh', $userJosh->name);

        $this->assertTrue($bug->project->manager === $bug->owner);

    }

}
?>
