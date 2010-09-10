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
        
        // We want to add another user that sorts to the top so that we can
        // be more certain that our order by and limit actions are working
        // as we expect.
        $topUser = new sample_User('000zero');
        $topUser = $session->add($topUser);
        $session->flush();
        
        $users = $session->find('sample_User')->orderBy('name')->all();
        $this->assertEquals(6, count($users));

        $users = $session->find('sample_User')->orderBy('name')->limit(1)->offset(4)->all();
        $this->assertEquals(1, count($users));
        $this->assertEquals('firstUser', $users[0]->name);
        
    }
    
    /**
     * Test getting a project managed by a user who favorites another project
     */
    public function testCircularReference() {
        $session = $this->getSampleProjectSession(true);
        $project = $session->find('sample_Project')->filterBy('projectId', 35569)->one();
        $manager = $project->manager;
        $favoriteProject = $manager->favoriteProject;
        $this->assertEquals(35570, $favoriteProject->projectId);
    }
    
    /**
     * Test getting a project managed by a user who favorites another project
     */
    public function testRootLevelObjectReference() {
        $session = $this->getSampleProjectSession(true);
        $project = $session->find('sample_Project')->filterBy('manager', 99990)->one();
        $manager = $project->manager;
        $favoriteProject = $manager->favoriteProject;
        $this->assertEquals(35570, $favoriteProject->projectId);
    }
    
    /**
     * Test selecting a related object
     */
    public function testSelectRelatedObject() {
        $session = $this->getSampleProjectSession(true);
        $manager = $session->query(
            'SELECT project.manager FROM sample_Project project WHERE project.projectId = :projectId'
        )->execute(array('projectId' => '35569',))->one();
        $this->assertEquals(99990, $manager->userId);
    }

    /**
     * Test identity of existing data
     */
    public function testExistingIdentity() {

        $session = $this->getSampleProjectSession(true);

        $bug = $session->find('sample_Bug')->filterBy('bugId', 521152)->one();
        $project = $session->find('sample_Project')->filterBy('projectId', 12345)->one();
        $user = $session->find('sample_User')->filterBy('userId', '55566')->one();

        $this->assertTrue($bug->owner === $project->manager);
        $this->assertTrue($user === $bug->owner);
        $this->assertTrue($user === $project->manager);
        
    }

    /**
     * Test identity of existing data (cross session)
     * 
     * All of the identity tests should fail when crossing from one
     * session into another. The same should be true between sessions
     * of one session factory and another as well.
     */
    public function testExistingIdentityCrossSession() {

        $sessionFactory = $this->getSampleProjectSessionFactory(true);
        
        $session1 = $sessionFactory->openSession();
        $session2 = $sessionFactory->openSession();
        $session3 = $sessionFactory->openSession();
        
        $bug = $session1->find('sample_Bug')->filterBy('bugId', 521152)->one();
        $project = $session2->find('sample_Project')->filterBy('projectId', 12345)->one();
        $user = $session3->find('sample_User')->filterBy('userId', '55566')->one();

        $this->assertTrue($bug->owner !== $project->manager);
        $this->assertTrue($user !== $bug->owner);
        $this->assertTrue($user !== $project->manager);
        
        $sessionFactory2 = $this->getSampleProjectSessionFactory(false);
        $sessionFactory3 = $this->getSampleProjectSessionFactory(false);
        
        $bug = $sessionFactory2->currentSession()->find('sample_Bug')->filterBy('bugId', 521152)->one();
        $user = $sessionFactory3->currentSession()->find('sample_User')->filterBy('userId', '55566')->one();
        $this->assertTrue($user !== $bug->owner);
        
    }
    
    /**
     * Test creating a new model with ID specified
     */
    public function testNewModelWithIdSpecified() {

        $this->loadClass('sample_Resource');

        $sessionFactory = $this->getSampleProjectSessionFactory(true);
        
        $session1 = $sessionFactory->openSession();
        $session2 = $sessionFactory->openSession();
        $session3 = $sessionFactory->openSession();
        
        $resource = $session1->add(new sample_Resource('inventory', 'Inventory'));
        
        $session1->flush();
        
        $this->assertEquals("inventory", $resource->resourceId);
        
        $resource = $session2->find('sample_Resource')->filterBy('resourceId', 'inventory')->one();
        $this->assertEquals("Inventory", $resource->name);
        
        $resource->name = 'Inventory Changed';
        
        $session2->flush();

        $resource = $session3->find('sample_Resource')->filterBy('resourceId', 'inventory')->one();
        $this->assertEquals("Inventory Changed", $resource->name);
        
    }
    
    /**
     * A model that uses a UUID
     */
    public function testNewModelWithUuidGenerator() {
        
        $this->loadClass('sample_UuidRecord');
        $session = $this->getSampleProjectSession(true);
        $record = $session->add(new sample_UuidRecord());
        
        $session->flush();
        
        $this->assertTrue(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $record->recordId, $matches) == 1);
    
    }

    /**
     * A silly record model
     */
    public function testNewModelWithSillyGenerator() {
        
        $this->loadClass('sample_SillyRecord');
        $session = $this->getSampleProjectSession(true);
        $record = $session->add(new sample_SillyRecord('FOO'));
        
        $this->assertEquals(null, $record->recordId);
        
        $session->flush();
        
        $this->assertEquals('SILLY-FOO', $record->recordId);
    
    }
    
    /**
     * A silly record model
     */
    public function testNewModelWithAddSillyGenerator() {
        
        $this->loadClass('sample_AddSillyRecord');
        $session = $this->getSampleProjectSession(true);
        $record = $session->add(new sample_AddSillyRecord('FOO'));
        
        $this->assertEquals('ADD-SILLY-FOO', $record->recordId);
        
    }
    
    /**
     * Get a sample project session
     * @return repose_Session
     */
    protected function getSampleProjectSessionFactory($initDb = false) {
        
        $this->loadClass('sample_SillyRecord');
        $this->loadClass('sample_AddSillyRecord');
        
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
                        'favoriteProject' => array(
                            'relationship' => 'many-to-one',
                            'className' => 'sample_Project',
                            'columnName' => 'favoriteProjectId'
                        ),
                    ),
                ),
                
                'sample_Resource' => array(
                    'tableName' => 'resource',
                    'properties' => array(
                        'resourceId' => array(
                            'primaryKey' => 'true',
                            'generator' => 'assigned',
                        ),
                        'name' => null,
                    ),
                ),
                
                'sample_UuidRecord' => array(
                    'tableName' => 'uuidRecord',
                    'properties' => array(
                        'recordId' => array(
                            'primaryKey' => true,
                            'generator' => 'uuid',
                        ),
                    ),
                ),

                'sample_SillyRecord' => array(
                    'tableName' => 'sillyRecord',
                    'properties' => array(
                        'recordId' => array(
                            'primaryKey' => true,
                            'generator' => 'sample_SillyRecordPropertyGenerator',
                        ),
                        'name' => null,
                    ),
                ),
                
                'sample_AddSillyRecord' => array(
                    'tableName' => 'addSillyRecord',
                    'properties' => array(
                        'recordId' => array(
                            'primaryKey' => true,
                            'generator' => 'sample_AddSillyRecordPropertyGenerator',
                        ),
                        'name' => null,
                    ),
                ),
                
            ),
        ));
        
        if ( $initDb ) {
            $this->initDb($configuration);
        }

        return new repose_ConfigurationSessionFactory($configuration);
        
    }
    
    /**
     * Initialize the testing database
     */
    public function initDb($configuration) {

        $dataSource = $configuration->dataSource();

        $dataSource->exec('DROP TABLE IF EXISTS user');
        $dataSource->exec('DROP TABLE IF EXISTS project');
        $dataSource->exec('DROP TABLE IF EXISTS projectInfo');
        $dataSource->exec('DROP TABLE IF EXISTS bug');
        $dataSource->exec('DROP TABLE IF EXISTS resource');
        $dataSource->exec('DROP TABLE IF EXISTS addSillyRecord');
        $dataSource->exec('DROP TABLE IF EXISTS sillyRecord');
        $dataSource->exec('DROP TABLE IF EXISTS uuidRecord');
        
        $dataSource->exec('
CREATE TABLE user (
userId INTEGER PRIMARY KEY AUTOINCREMENT,
name TEXT NOT NULL,
favoriteProjectId INTEGER
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

        $dataSource->exec('
CREATE TABLE resource (
resourceId TEXT NOT NULL,
name TEXT NOT NULL
)
');

        $dataSource->exec('
CREATE TABLE sillyRecord (
recordId TEXT NOT NULL,
name TEXT NOT NULL
)
');
        
        $dataSource->exec('
CREATE TABLE addSillyRecord (
recordId TEXT NOT NULL,
name TEXT NOT NULL
)
');
        
        $dataSource->exec('
CREATE TABLE uuidRecord (
recordId TEXT NOT NULL
)
');
        
        $dataSource->exec('INSERT INTO user (userId, name) VALUES (100001, "firstUser")');
        $dataSource->exec('INSERT INTO user (userId, name) VALUES (100002, "secondUser")');

        $dataSource->exec('INSERT INTO user (userId, name) VALUES (55566, "existingManager")');
        $dataSource->exec('INSERT INTO user (userId, name) VALUES (67387, "existingUser")');

        $dataSource->exec('INSERT INTO project (projectId, name, managerUserId) VALUES (12345, "Existing Project", 55566)');
        $dataSource->exec('INSERT INTO bug (bugId, title, body, projectId, reporterUserId, ownerUserId) VALUES (521152, "Existing Bug", "This bug existed from the time the database was created", 12345, 67387, 55566)');
        
        $dataSource->exec('INSERT INTO user (userId, name, favoriteProjectId) VALUES (99990, "circleUser", 35570)');
        
        $dataSource->exec('INSERT INTO project (projectId, name, managerUserId) VALUES (35569, "Circle Test", 99990)');
        $dataSource->exec('INSERT INTO project (projectId, name, managerUserId) VALUES (35570, "Circle Test (leaf)", 55566)');
        
    }
        
    
    /**
     * Get a sample project session
     * @return repose_Session
     */
    protected function getSampleProjectSession($initDb = false) {

        $sessionFactory = $this->getSampleProjectSessionFactory($initDb);
        return $sessionFactory->currentSession();

    }

}
?>
