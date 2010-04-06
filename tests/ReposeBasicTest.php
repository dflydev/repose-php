<?php

require_once('AbstractReposeTest.php');
require_once('lib/repose_Configuration.php');
require_once('lib/repose_ConfigurationSessionFactory.php');

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


    /**
     * Model test with persistence.
     */
    public function testSimpleModelUsageWithPersistence() {

        $session = $this->getSampleProjectSession(true);

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

        $pBug = $session->add($bug);

        $this->assertEquals('Something is broken', $pBug->title);
        $this->assertEquals('Click http://example.com/ to test!', $pBug->body);

        $this->assertEquals('josh', $pBug->reporter->name, 'Reporter');
        $this->assertEquals('beau', $pBug->owner->name, 'Owner');

        $this->assertEquals('Sample Project', $pBug->project->name, 'Bug\'s Project\'s name does not match');
        $this->assertEquals('beau', $pBug->project->manager->name, 'Manager');

        $this->assertEquals('Sample Project', $pBug->project->name);
        $this->assertEquals('beau', $pBug->project->manager->name, 'Manager');

        $this->assertEquals('beau', $pBug->owner->name);
        $this->assertEquals('josh', $pBug->reporter->name);

        $this->assertTrue($pBug->project->manager === $pBug->owner);

        $this->assertTrue($pBug->title === $pBug->project->bugs[0]->title);

    }

    /**
     * Test loading an existing bug
     */
    public function testLoadExistingBug() {

        $session = $this->getSampleProjectSession(true);

        $bug = $session->find('sample_Bug bug')->filterBy('bugId', 521152)->one();

        $this->assertEquals('Existing Bug', $bug->title);
        $this->assertEquals('This bug existed from the time the database was created', $bug->body);

        $this->assertEquals('existingUser', $bug->reporter->name, 'Reporter');
        $this->assertEquals('existingManager', $bug->owner->name, 'Owner');

        $this->assertEquals('Existing Project', $bug->project->name, 'Bug\'s Project\'s name does not match');
        $this->assertEquals('existingManager', $bug->project->manager->name, 'Manager');

        $this->assertEquals('Existing Bug', $bug->project->bugs[0]->title);

        $bug2 = $session->execute(
            'FROM sample_Bug bug WHERE bug.bugId = :bugId',
            array('bugId' => 521152)
        )->one();

        $this->assertEquals(521152, $bug2->bugId);

        $bug3 = $session->execute(
            'FROM sample_Bug WHERE bugId = :bugId',
            array('bugId' => 521152)
        )->one();

        $this->assertEquals(521152, $bug3->bugId);

        $bug4 = $session->find('sample_Bug')->filterBy('bugId', 521152)->one();
        $this->assertEquals(521152, $bug4->bugId);

    }

    /**
     * Get a sample project session
     * @return repose_Session
     */
    protected function getSampleProjectSession($initDb = false) {
        
        $configuration = new repose_Configuration(array(

            'connection' => array( 'dsn' => 'sqlite:' . dirname(__FILE__) . '/ReposeBasicTest.sq3', ),

            'classes' => array(

                'sample_Project' => array(
                    'tableName' => 'project',
                    'properties' => array(
                        'projectId' => array( 'primaryKey' => 'true', ),
                        'name' => null,
                        'manager' => array(
                            'relationship' => 'many-to-one',
                            'className' => 'sample_User',
                            'columnName' => 'managerUserId',
                        ),
                        'bugs' => array(
                            'relationship' => 'one-to-many',
                            'className' => 'sample_Bug',
                            'backref' => 'project',
                            'cascade' => 'delete-orphan',
                        ),
                    ),
                ),

                'sample_ProjectInfo' => array(
                    'tableName' => 'projectInfo',
                    'properties' => array(
                        'projectInfoId' => array( 'primaryKey' => 'true', ),
                        'description' => null,
                        'project' => array(
                            'relationship' => 'one-to-one',
                            'className' => 'sample_Project',
                        ),
                    ),
                ),

                'sample_Bug' => array(
                    'tableName' => 'bug',
                    'properties' => array(
                        'bugId' => array( 'primaryKey' => 'true', ),
                        'title' => null,
                        'body' => null,
                        'project' => array(
                            'relationship' => 'many-to-one',
                            'className' => 'sample_Project',
                        ),
                        'reporter' => array(
                            'relationship' => 'many-to-one',
                            'className' => 'sample_User',
                            'columnName' => 'reporterUserId',
                        ),
                        'owner' => array(
                            'relationship' => 'many-to-one',
                            'className' => 'sample_User',
                            'columnName' => 'ownerUserId',
                        ),
                    ),
                ),

                'sample_User' => array(
                    'tableName' => 'user',
                    'properties' => array(
                        'userId' => array( 'primaryKey' => 'true', ),
                        'name' => null,
                    ),
                ),

            ),
        ));

        $sessionFactory = new repose_ConfigurationSessionFactory($configuration);
        $session = $sessionFactory->currentSession();

        if ( $initDb ) {

            $dataSource = $configuration->dataSource();

            $dataSource->exec('DROP TABLE IF EXISTS user');
            $dataSource->exec('DROP TABLE IF EXISTS project');
            $dataSource->exec('DROP TABLE IF EXISTS projectInfo');
            $dataSource->exec('DROP TABLE IF EXISTS bug');

            $dataSource->exec('
CREATE TABLE user (
userId INTEGER PRIMARY KEY AUTOINCREMENT,
name TEXT NOT NULL
        )
');

            $dataSource->exec('
CREATE TABLE project (
projectId INTEGER PRIMARY KEY AUTOINCREMENT,
name TEXT NOT NULL,
managerUserId INTEGER NOT NULL
)
');

            $dataSource->exec('
CREATE TABLE projectInfo (
projectInfoId INTEGER PRIMARY KEY AUTOINCREMENT,
projectId INTEGER NOT NULL,
description TEXT NOT NULL
)
');

            $dataSource->exec('
CREATE TABLE bug (
bugId INTEGER PRIMARY KEY AUTOINCREMENT,
title TEXT NOT NULL,
body TEXT NOT NULL,
projectId INTEGER NOT NULL,
reporterUserId INTEGER NOT NULL,
ownerUserId INTEGER
)
');

            $dataSource->exec('INSERT INTO user (userId, name) VALUES (100001, "firstUser")');
            $dataSource->exec('INSERT INTO user (userId, name) VALUES (100002, "secondUser")');

            $dataSource->exec('INSERT INTO user (userId, name) VALUES (55566, "existingManager")');
            $dataSource->exec('INSERT INTO user (userId, name) VALUES (67387, "existingUser")');

            $dataSource->exec('INSERT INTO project (projectId, name, managerUserId) VALUES (12345, "Existing Project", 55566)');
            $dataSource->exec('INSERT INTO bug (bugId, title, body, projectId, reporterUserId, ownerUserId) VALUES (521152, "Existing Bug", "This bug existed from the time the database was created", 12345, 67387, 55566)');

        }

        return $session;

    }

}
?>
