<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * User Guide Command
 * 
 * Generates comprehensive user guides.
 */
class UserGuideCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:user-guide {--output=docs/user-guide.md : Output file path} {--detailed : Generate detailed guide}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate comprehensive user guide';

    /**
     * User guide data
     *
     * @var array<string, mixed>
     */
    protected array $userGuide = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📖 Generating User Guide...');
        
        $this->generateUserGuide();
        $this->createDocumentation();
        
        $outputPath = $this->option('output');
        $this->info("✅ User guide generated: {$outputPath}");
        
        return Command::SUCCESS;
    }

    /**
     * Generate user guide
     */
    private function generateUserGuide(): void
    {
        $this->userGuide = [
            'timestamp' => now()->toISOString(),
            'overview' => $this->generateOverview(),
            'getting_started' => $this->generateGettingStarted(),
            'features' => $this->generateFeatures(),
            'user_roles' => $this->generateUserRoles(),
            'navigation' => $this->generateNavigation(),
            'common_tasks' => $this->generateCommonTasks(),
            'troubleshooting' => $this->generateTroubleshooting(),
            'faq' => $this->generateFAQ(),
        ];
    }

    /**
     * Generate overview
     *
     * @return array<string, mixed>
     */
    private function generateOverview(): array
    {
        return [
            'title' => 'Service Learning Management System - User Guide',
            'description' => 'A comprehensive guide for users of the Service Learning Management System',
            'version' => '1.0.0',
            'last_updated' => now()->format('F j, Y'),
            'system_overview' => [
                'name' => 'Service Learning Management System',
                'purpose' => 'Manage and track service learning activities, projects, and student participation',
                'key_features' => [
                    'Project Management',
                    'Student Registration',
                    'Progress Tracking',
                    'Reporting and Analytics',
                    'Communication Tools',
                    'Document Management',
                ],
                'benefits' => [
                    'Streamlined project management',
                    'Enhanced student engagement',
                    'Improved tracking and reporting',
                    'Better communication',
                    'Centralized document storage',
                ],
            ],
        ];
    }

    /**
     * Generate getting started section
     *
     * @return array<string, mixed>
     */
    private function generateGettingStarted(): array
    {
        return [
            'system_requirements' => [
                'browser' => [
                    'Chrome 90+',
                    'Firefox 88+',
                    'Safari 14+',
                    'Edge 90+',
                ],
                'internet' => 'Stable internet connection required',
                'screen_resolution' => 'Minimum 1024x768 recommended',
            ],
            'accessing_the_system' => [
                'url' => 'https://your-domain.com',
                'login_credentials' => [
                    'username' => 'Your email address',
                    'password' => 'Your assigned password',
                ],
                'first_time_login' => [
                    'step1' => 'Navigate to the login page',
                    'step2' => 'Enter your credentials',
                    'step3' => 'Change your password if prompted',
                    'step4' => 'Complete your profile information',
                ],
            ],
            'dashboard_overview' => [
                'welcome_section' => 'Personalized welcome message and quick stats',
                'navigation_menu' => 'Main navigation for accessing different modules',
                'quick_actions' => 'Common tasks and shortcuts',
                'notifications' => 'System alerts and updates',
                'recent_activity' => 'Latest actions and updates',
            ],
            'profile_setup' => [
                'personal_information' => 'Update contact details and preferences',
                'profile_picture' => 'Upload and manage profile photo',
                'notification_settings' => 'Configure email and system notifications',
                'privacy_settings' => 'Manage privacy and visibility options',
            ],
        ];
    }

    /**
     * Generate features section
     *
     * @return array<string, mixed>
     */
    private function generateFeatures(): array
    {
        return [
            'project_management' => [
                'description' => 'Create, manage, and track service learning projects',
                'features' => [
                    'Project Creation' => 'Create new projects with detailed information',
                    'Project Dashboard' => 'View project overview and statistics',
                    'Task Management' => 'Assign and track project tasks',
                    'Timeline View' => 'Visual project timeline and milestones',
                    'Document Upload' => 'Upload and manage project documents',
                    'Progress Tracking' => 'Monitor project progress and completion',
                ],
                'how_to_use' => [
                    'Creating a Project' => [
                        'Navigate to Projects > Create New',
                        'Fill in project details and requirements',
                        'Set project timeline and milestones',
                        'Assign team members and roles',
                        'Save and publish the project',
                    ],
                    'Managing Projects' => [
                        'Access project dashboard',
                        'Update project information',
                        'Track progress and milestones',
                        'Manage team assignments',
                        'Generate project reports',
                    ],
                ],
            ],
            'student_registration' => [
                'description' => 'Register and manage student participation in projects',
                'features' => [
                    'Student Profiles' => 'Comprehensive student information management',
                    'Registration Process' => 'Streamlined project registration',
                    'Approval Workflow' => 'Coordinator approval for project participation',
                    'Schedule Management' => 'Manage student schedules and availability',
                    'Attendance Tracking' => 'Track student participation and attendance',
                ],
                'how_to_use' => [
                    'Student Registration' => [
                        'Browse available projects',
                        'Select desired project',
                        'Complete registration form',
                        'Submit for approval',
                        'Receive confirmation email',
                    ],
                    'Profile Management' => [
                        'Update personal information',
                        'Manage academic details',
                        'Track participation history',
                        'View certificates and achievements',
                    ],
                ],
            ],
            'progress_tracking' => [
                'description' => 'Monitor and track student progress and project completion',
                'features' => [
                    'Progress Dashboard' => 'Visual progress indicators and metrics',
                    'Milestone Tracking' => 'Track project milestones and deadlines',
                    'Time Logging' => 'Log hours and activities',
                    'Reflection Journals' => 'Document learning experiences',
                    'Assessment Tools' => 'Evaluate student performance and learning',
                ],
                'how_to_use' => [
                    'Tracking Progress' => [
                        'Access progress dashboard',
                        'Update milestone completion',
                        'Log hours and activities',
                        'Submit reflection entries',
                        'Review progress reports',
                    ],
                    'Assessment' => [
                        'Complete self-assessments',
                        'Submit reflection journals',
                        'Participate in peer reviews',
                        'Receive feedback from coordinators',
                    ],
                ],
            ],
            'reporting_analytics' => [
                'description' => 'Generate reports and analyze data for insights',
                'features' => [
                    'Custom Reports' => 'Create tailored reports for different needs',
                    'Data Visualization' => 'Charts and graphs for data analysis',
                    'Export Options' => 'Export reports in various formats',
                    'Real-time Analytics' => 'Live data and statistics',
                    'Historical Data' => 'Access to historical performance data',
                ],
                'how_to_use' => [
                    'Generating Reports' => [
                        'Select report type and parameters',
                        'Choose date range and filters',
                        'Generate and preview report',
                        'Export or share as needed',
                    ],
                    'Data Analysis' => [
                        'View analytics dashboard',
                        'Analyze trends and patterns',
                        'Compare performance metrics',
                        'Identify areas for improvement',
                    ],
                ],
            ],
            'communication_tools' => [
                'description' => 'Facilitate communication between students, coordinators, and partners',
                'features' => [
                    'Messaging System' => 'Direct messaging between users',
                    'Discussion Forums' => 'Project-specific discussion boards',
                    'Announcements' => 'System-wide and project announcements',
                    'Email Notifications' => 'Automated email notifications',
                    'Calendar Integration' => 'Sync with external calendars',
                ],
                'how_to_use' => [
                    'Sending Messages' => [
                        'Navigate to Messages',
                        'Select recipient or group',
                        'Compose and send message',
                        'Attach files if needed',
                        'Track message status',
                    ],
                    'Participating in Forums' => [
                        'Access project discussion forum',
                        'Read existing posts and replies',
                        'Create new posts or replies',
                        'Follow discussions of interest',
                    ],
                ],
            ],
            'document_management' => [
                'description' => 'Store, organize, and manage project documents and resources',
                'features' => [
                    'File Upload' => 'Upload various file types',
                    'Document Organization' => 'Categorize and organize documents',
                    'Version Control' => 'Track document versions and changes',
                    'Access Control' => 'Manage document permissions',
                    'Search Functionality' => 'Search and filter documents',
                ],
                'how_to_use' => [
                    'Uploading Documents' => [
                        'Navigate to Documents section',
                        'Select upload option',
                        'Choose file and category',
                        'Add description and tags',
                        'Set access permissions',
                        'Upload and confirm',
                    ],
                    'Managing Documents' => [
                        'Browse document library',
                        'Search for specific documents',
                        'Download or view documents',
                        'Update document information',
                        'Manage access permissions',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate user roles section
     *
     * @return array<string, mixed>
     */
    private function generateUserRoles(): array
    {
        return [
            'student' => [
                'description' => 'Students participating in service learning projects',
                'permissions' => [
                    'View available projects',
                    'Register for projects',
                    'Track personal progress',
                    'Submit reflections and journals',
                    'Access project documents',
                    'Communicate with team members',
                    'View personal reports',
                ],
                'responsibilities' => [
                    'Complete project requirements',
                    'Log hours and activities',
                    'Submit regular reflections',
                    'Participate in assessments',
                    'Maintain communication with coordinators',
                    'Follow project guidelines',
                ],
                'dashboard_features' => [
                    'My Projects' => 'View enrolled projects',
                    'Progress Tracker' => 'Monitor personal progress',
                    'Reflection Journal' => 'Submit learning reflections',
                    'Schedule' => 'View project schedule',
                    'Messages' => 'Communication center',
                ],
            ],
            'coordinator' => [
                'description' => 'Project coordinators managing service learning activities',
                'permissions' => [
                    'Create and manage projects',
                    'Approve student registrations',
                    'Track project progress',
                    'Generate reports',
                    'Manage project documents',
                    'Communicate with students and partners',
                    'Assess student performance',
                ],
                'responsibilities' => [
                    'Oversee project implementation',
                    'Monitor student progress',
                    'Provide guidance and support',
                    'Evaluate student performance',
                    'Maintain project documentation',
                    'Coordinate with community partners',
                ],
                'dashboard_features' => [
                    'Project Management' => 'Create and manage projects',
                    'Student Management' => 'Manage student registrations',
                    'Progress Monitoring' => 'Track project progress',
                    'Reporting' => 'Generate reports and analytics',
                    'Communication' => 'Manage communications',
                ],
            ],
            'administrator' => [
                'description' => 'System administrators with full access',
                'permissions' => [
                    'Manage all system settings',
                    'Create and manage user accounts',
                    'Configure system parameters',
                    'Access all data and reports',
                    'Manage system security',
                    'Monitor system performance',
                    'Backup and restore data',
                ],
                'responsibilities' => [
                    'Maintain system functionality',
                    'Ensure data security',
                    'Manage user access',
                    'Monitor system performance',
                    'Provide technical support',
                    'Implement system updates',
                ],
                'dashboard_features' => [
                    'User Management' => 'Manage user accounts',
                    'System Settings' => 'Configure system parameters',
                    'Security Management' => 'Manage security settings',
                    'Performance Monitoring' => 'Monitor system performance',
                    'Backup Management' => 'Manage data backups',
                ],
            ],
            'community_partner' => [
                'description' => 'Community organizations hosting service learning projects',
                'permissions' => [
                    'View project details',
                    'Track student progress',
                    'Provide feedback',
                    'Access project documents',
                    'Communicate with coordinators',
                    'Submit project evaluations',
                ],
                'responsibilities' => [
                    'Provide project opportunities',
                    'Supervise student activities',
                    'Provide feedback and evaluation',
                    'Maintain communication with coordinators',
                    'Ensure project success',
                ],
                'dashboard_features' => [
                    'Project Overview' => 'View project details',
                    'Student Progress' => 'Track student activities',
                    'Feedback System' => 'Provide evaluations',
                    'Communication' => 'Contact coordinators',
                    'Document Access' => 'Access project materials',
                ],
            ],
        ];
    }

    /**
     * Generate navigation section
     *
     * @return array<string, mixed>
     */
    private function generateNavigation(): array
    {
        return [
            'main_menu' => [
                'dashboard' => [
                    'description' => 'Main dashboard with overview and quick actions',
                    'access' => 'Always visible in top navigation',
                    'features' => [
                        'Quick stats and metrics',
                        'Recent activity feed',
                        'Quick action buttons',
                        'Notification center',
                    ],
                ],
                'projects' => [
                    'description' => 'Access to project management features',
                    'submenu' => [
                        'All Projects' => 'Browse all available projects',
                        'My Projects' => 'View enrolled projects (students)',
                        'Manage Projects' => 'Create and manage projects (coordinators)',
                        'Create New' => 'Create a new project (coordinators)',
                    ],
                ],
                'students' => [
                    'description' => 'Student management and registration',
                    'submenu' => [
                        'All Students' => 'View all registered students',
                        'Registrations' => 'Manage project registrations',
                        'Progress' => 'Track student progress',
                        'Reports' => 'Generate student reports',
                    ],
                ],
                'reports' => [
                    'description' => 'Reporting and analytics tools',
                    'submenu' => [
                        'Project Reports' => 'Generate project-specific reports',
                        'Student Reports' => 'Generate student performance reports',
                        'Analytics' => 'View system analytics',
                        'Export Data' => 'Export data in various formats',
                    ],
                ],
                'documents' => [
                    'description' => 'Document management and file storage',
                    'submenu' => [
                        'All Documents' => 'Browse all documents',
                        'Upload' => 'Upload new documents',
                        'Categories' => 'Manage document categories',
                        'Search' => 'Search documents',
                    ],
                ],
                'communication' => [
                    'description' => 'Communication tools and messaging',
                    'submenu' => [
                        'Messages' => 'Direct messaging system',
                        'Forums' => 'Discussion forums',
                        'Announcements' => 'System announcements',
                        'Calendar' => 'Event calendar',
                    ],
                ],
                'settings' => [
                    'description' => 'User and system settings',
                    'submenu' => [
                        'Profile' => 'User profile settings',
                        'Preferences' => 'User preferences',
                        'Notifications' => 'Notification settings',
                        'Security' => 'Security settings',
                    ],
                ],
            ],
            'breadcrumb_navigation' => [
                'description' => 'Shows current location in the system',
                'usage' => 'Click on breadcrumb items to navigate back',
                'example' => 'Dashboard > Projects > My Projects > Project Name',
            ],
            'search_functionality' => [
                'description' => 'Global search across the system',
                'access' => 'Search bar in top navigation',
                'searchable_items' => [
                    'Projects',
                    'Students',
                    'Documents',
                    'Messages',
                    'Announcements',
                ],
                'search_tips' => [
                    'Use keywords for better results',
                    'Use quotes for exact phrases',
                    'Use filters to narrow results',
                    'Search is case-insensitive',
                ],
            ],
        ];
    }

    /**
     * Generate common tasks section
     *
     * @return array<string, mixed>
     */
    private function generateCommonTasks(): array
    {
        return [
            'for_students' => [
                'registering_for_project' => [
                    'description' => 'How to register for a service learning project',
                    'steps' => [
                        'Navigate to Projects > All Projects',
                        'Browse available projects',
                        'Click on desired project',
                        'Review project details and requirements',
                        'Click "Register for Project"',
                        'Complete registration form',
                        'Submit for approval',
                        'Wait for coordinator approval',
                        'Receive confirmation email',
                    ],
                    'tips' => [
                        'Read project requirements carefully',
                        'Ensure you meet eligibility criteria',
                        'Check your schedule availability',
                        'Contact coordinator if you have questions',
                    ],
                ],
                'logging_hours' => [
                    'description' => 'How to log your service learning hours',
                    'steps' => [
                        'Navigate to your project dashboard',
                        'Click on "Log Hours"',
                        'Select date and time period',
                        'Describe activities performed',
                        'Add any relevant notes',
                        'Submit time log',
                        'Wait for coordinator approval',
                    ],
                    'tips' => [
                        'Log hours promptly after each session',
                        'Be specific about activities performed',
                        'Include learning outcomes',
                        'Keep backup records',
                    ],
                ],
                'submitting_reflection' => [
                    'description' => 'How to submit reflection journals',
                    'steps' => [
                        'Navigate to Progress > Reflection Journal',
                        'Click "New Reflection"',
                        'Select reflection type',
                        'Write your reflection',
                        'Attach any relevant documents',
                        'Submit reflection',
                    ],
                    'tips' => [
                        'Reflect on learning experiences',
                        'Connect theory to practice',
                        'Include specific examples',
                        'Be honest and thoughtful',
                    ],
                ],
            ],
            'for_coordinators' => [
                'creating_project' => [
                    'description' => 'How to create a new service learning project',
                    'steps' => [
                        'Navigate to Projects > Create New',
                        'Fill in basic project information',
                        'Set project requirements and goals',
                        'Define timeline and milestones',
                        'Specify student requirements',
                        'Add project documents',
                        'Set approval workflow',
                        'Publish project',
                    ],
                    'tips' => [
                        'Be clear about project objectives',
                        'Set realistic timelines',
                        'Include all necessary information',
                        'Review before publishing',
                    ],
                ],
                'approving_registrations' => [
                    'description' => 'How to approve student registrations',
                    'steps' => [
                        'Navigate to Students > Registrations',
                        'View pending registrations',
                        'Review student information',
                        'Check eligibility criteria',
                        'Approve or reject registration',
                        'Send notification to student',
                    ],
                    'tips' => [
                        'Review student qualifications carefully',
                        'Consider project capacity',
                        'Communicate decisions promptly',
                        'Provide feedback when rejecting',
                    ],
                ],
                'generating_reports' => [
                    'description' => 'How to generate project reports',
                    'steps' => [
                        'Navigate to Reports > Project Reports',
                        'Select report type',
                        'Choose project and date range',
                        'Select report parameters',
                        'Generate report',
                        'Review and export',
                    ],
                    'tips' => [
                        'Choose appropriate report type',
                        'Set relevant date ranges',
                        'Include all necessary data',
                        'Export in required format',
                    ],
                ],
            ],
            'for_administrators' => [
                'managing_users' => [
                    'description' => 'How to manage user accounts',
                    'steps' => [
                        'Navigate to Settings > User Management',
                        'View user list',
                        'Create new user account',
                        'Edit user information',
                        'Set user permissions',
                        'Deactivate/reactivate accounts',
                    ],
                    'tips' => [
                        'Verify user information',
                        'Set appropriate permissions',
                        'Maintain security standards',
                        'Document changes',
                    ],
                ],
                'system_configuration' => [
                    'description' => 'How to configure system settings',
                    'steps' => [
                        'Navigate to Settings > System Configuration',
                        'Review current settings',
                        'Modify configuration parameters',
                        'Test changes in development',
                        'Apply changes to production',
                        'Monitor system performance',
                    ],
                    'tips' => [
                        'Backup before making changes',
                        'Test in development environment',
                        'Document all changes',
                        'Monitor for issues',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate troubleshooting section
     *
     * @return array<string, mixed>
     */
    private function generateTroubleshooting(): array
    {
        return [
            'login_issues' => [
                'forgot_password' => [
                    'problem' => 'Cannot remember password',
                    'solution' => [
                        'Click "Forgot Password" on login page',
                        'Enter your email address',
                        'Check email for reset link',
                        'Click link and set new password',
                        'Login with new password',
                    ],
                ],
                'account_locked' => [
                    'problem' => 'Account is locked after failed attempts',
                    'solution' => [
                        'Wait 15 minutes for automatic unlock',
                        'Contact system administrator',
                        'Provide identification for account reset',
                    ],
                ],
                'invalid_credentials' => [
                    'problem' => 'Username or password incorrect',
                    'solution' => [
                        'Check username spelling',
                        'Ensure caps lock is off',
                        'Try password reset',
                        'Contact administrator if issues persist',
                    ],
                ],
            ],
            'navigation_issues' => [
                'page_not_found' => [
                    'problem' => '404 error or page not found',
                    'solution' => [
                        'Check URL spelling',
                        'Use navigation menu instead of direct links',
                        'Clear browser cache',
                        'Contact administrator if persistent',
                    ],
                ],
                'slow_loading' => [
                    'problem' => 'Pages load slowly',
                    'solution' => [
                        'Check internet connection',
                        'Clear browser cache',
                        'Try different browser',
                        'Contact administrator if persistent',
                    ],
                ],
            ],
            'data_issues' => [
                'missing_data' => [
                    'problem' => 'Expected data not appearing',
                    'solution' => [
                        'Refresh the page',
                        'Check filters and search terms',
                        'Verify permissions',
                        'Contact administrator',
                    ],
                ],
                'save_errors' => [
                    'problem' => 'Cannot save changes',
                    'solution' => [
                        'Check required fields',
                        'Verify data format',
                        'Try again in a few minutes',
                        'Contact administrator if persistent',
                    ],
                ],
            ],
            'technical_issues' => [
                'browser_compatibility' => [
                    'problem' => 'System not working properly in browser',
                    'solution' => [
                        'Update browser to latest version',
                        'Try different browser',
                        'Clear browser cache and cookies',
                        'Disable browser extensions',
                    ],
                ],
                'file_upload_issues' => [
                    'problem' => 'Cannot upload files',
                    'solution' => [
                        'Check file size limits',
                        'Verify file type is allowed',
                        'Try smaller file size',
                        'Contact administrator if persistent',
                    ],
                ],
            ],
            'contact_support' => [
                'when_to_contact' => [
                    'Technical issues not resolved by troubleshooting',
                    'Account access problems',
                    'Data accuracy concerns',
                    'Feature requests or suggestions',
                    'Security concerns',
                ],
                'how_to_contact' => [
                    'Email: support@your-domain.com',
                    'Phone: (555) 123-4567',
                    'Support hours: Monday-Friday, 9 AM - 5 PM',
                    'Include detailed description of issue',
                    'Provide screenshots if helpful',
                ],
                'information_to_provide' => [
                    'Your name and contact information',
                    'User role (student, coordinator, etc.)',
                    'Detailed description of the problem',
                    'Steps to reproduce the issue',
                    'Browser and operating system',
                    'Error messages if any',
                ],
            ],
        ];
    }

    /**
     * Generate FAQ section
     *
     * @return array<string, mixed>
     */
    private function generateFAQ(): array
    {
        return [
            'general' => [
                'what_is_service_learning' => [
                    'question' => 'What is service learning?',
                    'answer' => 'Service learning is an educational approach that combines community service with academic learning. Students apply classroom knowledge to real-world problems while serving their communities.',
                ],
                'system_purpose' => [
                    'question' => 'What is the purpose of this system?',
                    'answer' => 'This system helps manage service learning projects, track student participation, and facilitate communication between students, coordinators, and community partners.',
                ],
                'who_can_use' => [
                    'question' => 'Who can use this system?',
                    'answer' => 'Students, project coordinators, community partners, and administrators can use the system based on their roles and permissions.',
                ],
            ],
            'students' => [
                'how_to_register' => [
                    'question' => 'How do I register for a project?',
                    'answer' => 'Browse available projects, select one that interests you, and click "Register for Project" to submit your application.',
                ],
                'hours_requirement' => [
                    'question' => 'How many hours do I need to complete?',
                    'answer' => 'Hour requirements vary by project. Check the project details for specific requirements.',
                ],
                'reflection_requirements' => [
                    'question' => 'How often do I need to submit reflections?',
                    'answer' => 'Reflection requirements vary by project. Your coordinator will provide specific guidelines.',
                ],
            ],
            'coordinators' => [
                'creating_projects' => [
                    'question' => 'How do I create a new project?',
                    'answer' => 'Navigate to Projects > Create New and fill in the required information about your project.',
                ],
                'approving_students' => [
                    'question' => 'How do I approve student registrations?',
                    'answer' => 'Go to Students > Registrations to view and approve pending student applications.',
                ],
                'tracking_progress' => [
                    'question' => 'How can I track student progress?',
                    'answer' => 'Use the Progress Monitoring dashboard to view student activities, hours logged, and reflection submissions.',
                ],
            ],
            'technical' => [
                'browser_requirements' => [
                    'question' => 'What browsers are supported?',
                    'answer' => 'The system works best with Chrome, Firefox, Safari, and Edge (latest versions).',
                ],
                'mobile_access' => [
                    'question' => 'Can I access the system on mobile devices?',
                    'answer' => 'Yes, the system is responsive and works on mobile devices, though some features may be optimized for desktop use.',
                ],
                'data_security' => [
                    'question' => 'How is my data protected?',
                    'answer' => 'We use industry-standard security measures to protect your data, including encryption and secure authentication.',
                ],
            ],
        ];
    }

    /**
     * Create documentation
     */
    private function createDocumentation(): void
    {
        $outputPath = $this->option('output');
        $content = $this->generateMarkdownContent();
        
        // Ensure directory exists
        $directory = dirname($outputPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        File::put($outputPath, $content);
    }

    /**
     * Generate markdown content
     *
     * @return string
     */
    private function generateMarkdownContent(): string
    {
        $content = "# {$this->userGuide['overview']['title']}\n\n";
        $content .= "**Version**: {$this->userGuide['overview']['version']}\n";
        $content .= "**Last Updated**: {$this->userGuide['overview']['last_updated']}\n\n";
        
        // Overview
        $content .= $this->generateOverviewSection();
        
        // Getting Started
        $content .= $this->generateGettingStartedSection();
        
        // Features
        $content .= $this->generateFeaturesSection();
        
        // User Roles
        $content .= $this->generateUserRolesSection();
        
        // Navigation
        $content .= $this->generateNavigationSection();
        
        // Common Tasks
        $content .= $this->generateCommonTasksSection();
        
        // Troubleshooting
        $content .= $this->generateTroubleshootingSection();
        
        // FAQ
        $content .= $this->generateFAQSection();
        
        return $content;
    }

    // Helper methods for generating sections...
    private function generateOverviewSection(): string
    {
        $overview = $this->userGuide['overview'];
        
        $content = "## Overview\n\n";
        $content .= "{$overview['description']}\n\n";
        
        $content .= "### System Overview\n";
        $content .= "**Name**: {$overview['system_overview']['name']}\n";
        $content .= "**Purpose**: {$overview['system_overview']['purpose']}\n\n";
        
        $content .= "### Key Features\n";
        foreach ($overview['system_overview']['key_features'] as $feature) {
            $content .= "- {$feature}\n";
        }
        $content .= "\n";
        
        $content .= "### Benefits\n";
        foreach ($overview['system_overview']['benefits'] as $benefit) {
            $content .= "- {$benefit}\n";
        }
        $content .= "\n";
        
        return $content;
    }

    private function generateGettingStartedSection(): string
    {
        $gettingStarted = $this->userGuide['getting_started'];
        
        $content = "## Getting Started\n\n";
        
        $content .= "### System Requirements\n";
        $content .= "**Supported Browsers**:\n";
        foreach ($gettingStarted['system_requirements']['browser'] as $browser) {
            $content .= "- {$browser}\n";
        }
        $content .= "\n";
        $content .= "**Internet**: {$gettingStarted['system_requirements']['internet']}\n";
        $content .= "**Screen Resolution**: {$gettingStarted['system_requirements']['screen_resolution']}\n\n";
        
        $content .= "### Accessing the System\n";
        $content .= "**URL**: {$gettingStarted['accessing_the_system']['url']}\n\n";
        
        $content .= "**Login Credentials**:\n";
        $content .= "- **Username**: {$gettingStarted['accessing_the_system']['login_credentials']['username']}\n";
        $content .= "- **Password**: {$gettingStarted['accessing_the_system']['login_credentials']['password']}\n\n";
        
        $content .= "**First Time Login**:\n";
        foreach ($gettingStarted['accessing_the_system']['first_time_login'] as $step => $description) {
            $content .= "1. {$description}\n";
        }
        $content .= "\n";
        
        $content .= "### Dashboard Overview\n";
        foreach ($gettingStarted['dashboard_overview'] as $feature => $description) {
            $content .= "- **{$feature}**: {$description}\n";
        }
        $content .= "\n";
        
        $content .= "### Profile Setup\n";
        foreach ($gettingStarted['profile_setup'] as $setting => $description) {
            $content .= "- **{$setting}**: {$description}\n";
        }
        $content .= "\n";
        
        return $content;
    }

    private function generateFeaturesSection(): string
    {
        $features = $this->userGuide['features'];
        
        $content = "## Features\n\n";
        
        foreach ($features as $featureName => $feature) {
            $content .= "### {$featureName}\n";
            $content .= "{$feature['description']}\n\n";
            
            $content .= "**Features**:\n";
            foreach ($feature['features'] as $subFeature => $description) {
                $content .= "- **{$subFeature}**: {$description}\n";
            }
            $content .= "\n";
            
            if (isset($feature['how_to_use'])) {
                $content .= "**How to Use**:\n";
                foreach ($feature['how_to_use'] as $task => $steps) {
                    $content .= "#### {$task}\n";
                    foreach ($steps as $step) {
                        $content .= "1. {$step}\n";
                    }
                    $content .= "\n";
                }
            }
        }
        
        return $content;
    }

    private function generateUserRolesSection(): string
    {
        $userRoles = $this->userGuide['user_roles'];
        
        $content = "## User Roles\n\n";
        
        foreach ($userRoles as $role => $roleInfo) {
            $content .= "### {$role}\n";
            $content .= "{$roleInfo['description']}\n\n";
            
            $content .= "**Permissions**:\n";
            foreach ($roleInfo['permissions'] as $permission) {
                $content .= "- {$permission}\n";
            }
            $content .= "\n";
            
            $content .= "**Responsibilities**:\n";
            foreach ($roleInfo['responsibilities'] as $responsibility) {
                $content .= "- {$responsibility}\n";
            }
            $content .= "\n";
            
            $content .= "**Dashboard Features**:\n";
            foreach ($roleInfo['dashboard_features'] as $feature => $description) {
                $content .= "- **{$feature}**: {$description}\n";
            }
            $content .= "\n";
        }
        
        return $content;
    }

    private function generateNavigationSection(): string
    {
        $navigation = $this->userGuide['navigation'];
        
        $content = "## Navigation\n\n";
        
        $content .= "### Main Menu\n";
        foreach ($navigation['main_menu'] as $menuItem => $menuInfo) {
            $content .= "#### {$menuItem}\n";
            $content .= "{$menuInfo['description']}\n";
            
            if (isset($menuInfo['submenu'])) {
                $content .= "\n**Submenu**:\n";
                foreach ($menuInfo['submenu'] as $subItem => $description) {
                    $content .= "- **{$subItem}**: {$description}\n";
                }
            }
            
            if (isset($menuInfo['features'])) {
                $content .= "\n**Features**:\n";
                foreach ($menuInfo['features'] as $feature) {
                    $content .= "- {$feature}\n";
                }
            }
            $content .= "\n";
        }
        
        $content .= "### Breadcrumb Navigation\n";
        $content .= "{$navigation['breadcrumb_navigation']['description']}\n";
        $content .= "**Usage**: {$navigation['breadcrumb_navigation']['usage']}\n";
        $content .= "**Example**: {$navigation['breadcrumb_navigation']['example']}\n\n";
        
        $content .= "### Search Functionality\n";
        $content .= "{$navigation['search_functionality']['description']}\n";
        $content .= "**Access**: {$navigation['search_functionality']['access']}\n\n";
        
        $content .= "**Searchable Items**:\n";
        foreach ($navigation['search_functionality']['searchable_items'] as $item) {
            $content .= "- {$item}\n";
        }
        $content .= "\n";
        
        $content .= "**Search Tips**:\n";
        foreach ($navigation['search_functionality']['search_tips'] as $tip) {
            $content .= "- {$tip}\n";
        }
        $content .= "\n";
        
        return $content;
    }

    private function generateCommonTasksSection(): string
    {
        $commonTasks = $this->userGuide['common_tasks'];
        
        $content = "## Common Tasks\n\n";
        
        foreach ($commonTasks as $userType => $tasks) {
            $content .= "### For {$userType}\n";
            
            foreach ($tasks as $taskName => $task) {
                $content .= "#### {$taskName}\n";
                $content .= "{$task['description']}\n\n";
                
                $content .= "**Steps**:\n";
                foreach ($task['steps'] as $step) {
                    $content .= "1. {$step}\n";
                }
                $content .= "\n";
                
                if (isset($task['tips'])) {
                    $content .= "**Tips**:\n";
                    foreach ($task['tips'] as $tip) {
                        $content .= "- {$tip}\n";
                    }
                    $content .= "\n";
                }
            }
        }
        
        return $content;
    }

    private function generateTroubleshootingSection(): string
    {
        $troubleshooting = $this->userGuide['troubleshooting'];
        
        $content = "## Troubleshooting\n\n";
        
        foreach ($troubleshooting as $category => $issues) {
            $content .= "### {$category}\n";
            
            foreach ($issues as $issueName => $issue) {
                $content .= "#### {$issueName}\n";
                $content .= "**Problem**: {$issue['problem']}\n\n";
                
                $content .= "**Solution**:\n";
                foreach ($issue['solution'] as $step) {
                    $content .= "1. {$step}\n";
                }
                $content .= "\n";
            }
        }
        
        return $content;
    }

    private function generateFAQSection(): string
    {
        $faq = $this->userGuide['faq'];
        
        $content = "## Frequently Asked Questions\n\n";
        
        foreach ($faq as $category => $questions) {
            $content .= "### {$category}\n";
            
            foreach ($questions as $questionKey => $qa) {
                $content .= "**Q: {$qa['question']}**\n";
                $content .= "A: {$qa['answer']}\n\n";
            }
        }
        
        return $content;
    }
} 