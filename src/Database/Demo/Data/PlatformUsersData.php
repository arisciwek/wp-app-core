<?php
/**
 * Platform Users Data
 *
 * @package     WP_App_Core
 * @subpackage  Database/Demo/Data
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Database/Demo/Data/PlatformUsersData.php
 *
 * Description: Static platform user data for demo generation.
 *              Used by WPUserGenerator and PlatformDemoData.
 *              20 users total (ID 230-249) across 7 platform roles.
 *              Names are completely unique to avoid conflicts with other plugins.
 *
 * Note: All users get dual roles:
 *       1. platform_staff (base role for wp-admin access)
 *       2. platform_xxx (admin role for specific permissions)
 */

namespace WPAppCore\Database\Demo\Data;

defined('ABSPATH') || exit;

class PlatformUsersData {
    // Constants for user ID ranges
    const USER_ID_START = 230;
    const USER_ID_END = 249;

    /**
     * Name collection for generating unique platform staff names
     * All names must use words from this collection only
     * MUST BE DIFFERENT from all wp-customer and wp-agency plugin collections
     */
    private static $name_collection = [
        'Arman', 'Benny', 'Clara', 'Diana', 'Edwin', 'Felix',
        'Grace', 'Helen', 'Ivan', 'Julia', 'Kevin', 'Laura',
        'Marco', 'Nita', 'Oscar', 'Paula', 'Quinn', 'Rita',
        'Steven', 'Tina', 'Victor', 'Wendy', 'Xavier', 'Yolanda', 'Zack'
    ];

    /**
     * Department assignments for platform staff
     */
    private static $departments = [
        'Management',
        'Operations',
        'IT & Development',
        'Finance',
        'Support',
        'Analytics',
        'General'
    ];

    /**
     * Static platform user data
     * Names generated from $name_collection (2 words combination)
     * Each name is unique and uses only words from the collection
     *
     * Distribution:
     * - Platform Super Admin: 2 users (230-231)
     * - Platform Admin: 3 users (232-234)
     * - Platform Manager: 3 users (235-237)
     * - Platform Support: 4 users (238-241)
     * - Platform Finance: 3 users (242-244)
     * - Platform Analyst: 3 users (245-247)
     * - Platform Viewer: 2 users (248-249)
     */
    public static $data = [
        // Platform Super Admin (2 users) - Management Department
        230 => [
            'id' => 230,
            'username' => 'arman_benny',
            'display_name' => 'Arman Benny',
            'email' => 'arman.benny@platform.local',
            'roles' => ['platform_staff', 'platform_super_admin'],
            'employee_id' => 'STAFF-001',
            'department' => 'Management',
            'hire_date' => '2023-01-15',
            'phone' => '+62 821-1111-0001'
        ],
        231 => [
            'id' => 231,
            'username' => 'clara_diana',
            'display_name' => 'Clara Diana',
            'email' => 'clara.diana@platform.local',
            'roles' => ['platform_staff', 'platform_super_admin'],
            'employee_id' => 'STAFF-002',
            'department' => 'Management',
            'hire_date' => '2023-02-01',
            'phone' => '+62 821-1111-0002'
        ],

        // Platform Admin (3 users) - Operations Department
        232 => [
            'id' => 232,
            'username' => 'edwin_felix',
            'display_name' => 'Edwin Felix',
            'email' => 'edwin.felix@platform.local',
            'roles' => ['platform_staff', 'platform_admin'],
            'employee_id' => 'STAFF-003',
            'department' => 'Operations',
            'hire_date' => '2023-03-10',
            'phone' => '+62 821-1111-0003'
        ],
        233 => [
            'id' => 233,
            'username' => 'grace_helen',
            'display_name' => 'Grace Helen',
            'email' => 'grace.helen@platform.local',
            'roles' => ['platform_staff', 'platform_admin'],
            'employee_id' => 'STAFF-004',
            'department' => 'Operations',
            'hire_date' => '2023-03-15',
            'phone' => '+62 821-1111-0004'
        ],
        234 => [
            'id' => 234,
            'username' => 'ivan_julia',
            'display_name' => 'Ivan Julia',
            'email' => 'ivan.julia@platform.local',
            'roles' => ['platform_staff', 'platform_admin'],
            'employee_id' => 'STAFF-005',
            'department' => 'Operations',
            'hire_date' => '2023-04-01',
            'phone' => '+62 821-1111-0005'
        ],

        // Platform Manager (3 users) - Mixed Departments
        235 => [
            'id' => 235,
            'username' => 'kevin_laura',
            'display_name' => 'Kevin Laura',
            'email' => 'kevin.laura@platform.local',
            'roles' => ['platform_staff', 'platform_manager'],
            'employee_id' => 'STAFF-006',
            'department' => 'Management',
            'hire_date' => '2023-04-15',
            'phone' => '+62 821-1111-0006'
        ],
        236 => [
            'id' => 236,
            'username' => 'marco_nita',
            'display_name' => 'Marco Nita',
            'email' => 'marco.nita@platform.local',
            'roles' => ['platform_staff', 'platform_manager'],
            'employee_id' => 'STAFF-007',
            'department' => 'Operations',
            'hire_date' => '2023-05-01',
            'phone' => '+62 821-1111-0007'
        ],
        237 => [
            'id' => 237,
            'username' => 'oscar_paula',
            'display_name' => 'Oscar Paula',
            'email' => 'oscar.paula@platform.local',
            'roles' => ['platform_staff', 'platform_manager'],
            'employee_id' => 'STAFF-008',
            'department' => 'IT & Development',
            'hire_date' => '2023-05-15',
            'phone' => '+62 821-1111-0008'
        ],

        // Platform Support (4 users) - Support Department
        238 => [
            'id' => 238,
            'username' => 'quinn_rita',
            'display_name' => 'Quinn Rita',
            'email' => 'quinn.rita@platform.local',
            'roles' => ['platform_staff', 'platform_support'],
            'employee_id' => 'STAFF-009',
            'department' => 'Support',
            'hire_date' => '2023-06-01',
            'phone' => '+62 821-1111-0009'
        ],
        239 => [
            'id' => 239,
            'username' => 'steven_tina',
            'display_name' => 'Steven Tina',
            'email' => 'steven.tina@platform.local',
            'roles' => ['platform_staff', 'platform_support'],
            'employee_id' => 'STAFF-010',
            'department' => 'Support',
            'hire_date' => '2023-06-10',
            'phone' => '+62 821-1111-0010'
        ],
        240 => [
            'id' => 240,
            'username' => 'victor_wendy',
            'display_name' => 'Victor Wendy',
            'email' => 'victor.wendy@platform.local',
            'roles' => ['platform_staff', 'platform_support'],
            'employee_id' => 'STAFF-011',
            'department' => 'Support',
            'hire_date' => '2023-06-15',
            'phone' => '+62 821-1111-0011'
        ],
        241 => [
            'id' => 241,
            'username' => 'xavier_yolanda',
            'display_name' => 'Xavier Yolanda',
            'email' => 'xavier.yolanda@platform.local',
            'roles' => ['platform_staff', 'platform_support'],
            'employee_id' => 'STAFF-012',
            'department' => 'Support',
            'hire_date' => '2023-07-01',
            'phone' => '+62 821-1111-0012'
        ],

        // Platform Finance (3 users) - Finance Department
        242 => [
            'id' => 242,
            'username' => 'zack_arman',
            'display_name' => 'Zack Arman',
            'email' => 'zack.arman@platform.local',
            'roles' => ['platform_staff', 'platform_finance'],
            'employee_id' => 'STAFF-013',
            'department' => 'Finance',
            'hire_date' => '2023-07-10',
            'phone' => '+62 821-1111-0013'
        ],
        243 => [
            'id' => 243,
            'username' => 'benny_clara',
            'display_name' => 'Benny Clara',
            'email' => 'benny.clara@platform.local',
            'roles' => ['platform_staff', 'platform_finance'],
            'employee_id' => 'STAFF-014',
            'department' => 'Finance',
            'hire_date' => '2023-07-15',
            'phone' => '+62 821-1111-0014'
        ],
        244 => [
            'id' => 244,
            'username' => 'diana_edwin',
            'display_name' => 'Diana Edwin',
            'email' => 'diana.edwin@platform.local',
            'roles' => ['platform_staff', 'platform_finance'],
            'employee_id' => 'STAFF-015',
            'department' => 'Finance',
            'hire_date' => '2023-08-01',
            'phone' => '+62 821-1111-0015'
        ],

        // Platform Analyst (3 users) - Analytics Department
        245 => [
            'id' => 245,
            'username' => 'felix_grace',
            'display_name' => 'Felix Grace',
            'email' => 'felix.grace@platform.local',
            'roles' => ['platform_staff', 'platform_analyst'],
            'employee_id' => 'STAFF-016',
            'department' => 'Analytics',
            'hire_date' => '2023-08-10',
            'phone' => '+62 821-1111-0016'
        ],
        246 => [
            'id' => 246,
            'username' => 'helen_ivan',
            'display_name' => 'Helen Ivan',
            'email' => 'helen.ivan@platform.local',
            'roles' => ['platform_staff', 'platform_analyst'],
            'employee_id' => 'STAFF-017',
            'department' => 'Analytics',
            'hire_date' => '2023-08-15',
            'phone' => '+62 821-1111-0017'
        ],
        247 => [
            'id' => 247,
            'username' => 'julia_kevin',
            'display_name' => 'Julia Kevin',
            'email' => 'julia.kevin@platform.local',
            'roles' => ['platform_staff', 'platform_analyst'],
            'employee_id' => 'STAFF-018',
            'department' => 'Analytics',
            'hire_date' => '2023-09-01',
            'phone' => '+62 821-1111-0018'
        ],

        // Platform Viewer (2 users) - General Department
        248 => [
            'id' => 248,
            'username' => 'laura_marco',
            'display_name' => 'Laura Marco',
            'email' => 'laura.marco@platform.local',
            'roles' => ['platform_staff', 'platform_viewer'],
            'employee_id' => 'STAFF-019',
            'department' => 'General',
            'hire_date' => '2023-09-10',
            'phone' => '+62 821-1111-0019'
        ],
        249 => [
            'id' => 249,
            'username' => 'nita_oscar',
            'display_name' => 'Nita Oscar',
            'email' => 'nita.oscar@platform.local',
            'roles' => ['platform_staff', 'platform_viewer'],
            'employee_id' => 'STAFF-020',
            'department' => 'General',
            'hire_date' => '2023-09-15',
            'phone' => '+62 821-1111-0020'
        ],
    ];

    /**
     * Get name collection
     *
     * @return array Collection of name words
     */
    public static function getNameCollection(): array {
        return self::$name_collection;
    }

    /**
     * Get all departments
     *
     * @return array List of departments
     */
    public static function getDepartments(): array {
        return self::$departments;
    }

    /**
     * Validate if a name uses only words from collection
     *
     * @param string $name Full name to validate (e.g., "Arman Benny")
     * @return bool True if all words are from collection
     */
    public static function isValidName(string $name): bool {
        $words = explode(' ', $name);
        foreach ($words as $word) {
            if (!in_array($word, self::$name_collection)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get user count by role
     *
     * @param string $role Role slug
     * @return int Count of users with that role
     */
    public static function getUserCountByRole(string $role): int {
        $count = 0;
        foreach (self::$data as $user) {
            if (in_array($role, $user['roles'])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get all user data
     *
     * @return array All user data
     */
    public static function getData(): array {
        return self::$data;
    }
}
