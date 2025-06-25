@echo off
echo Running Command Tests...
echo.

echo === Running Web3 Commands Test ===
vendor\bin\phpunit tests\Feature\Commands\Web3CommandsTest.php --testdox
echo.

echo === Running Health Monitor Commands Test ===
vendor\bin\phpunit tests\Feature\Commands\HealthMonitorCommandsTest.php --testdox
echo.

echo === Running Infrastructure Commands Test ===
vendor\bin\phpunit tests\Feature\Commands\InfrastructureCommandsTest.php --testdox
echo.

echo === Running Codespace Commands Test ===
vendor\bin\phpunit tests\Feature\Commands\CodespaceCommandsTest.php --testdox
echo.

echo === Running Utility Commands Test ===
vendor\bin\phpunit tests\Feature\Commands\UtilityCommandsTest.php --testdox
echo.

echo === Running Analytics Commands Test ===
vendor\bin\phpunit tests\Feature\Commands\AnalyticsCommandsTest.php --testdox
echo.

echo === Running Config Commands Test ===
vendor\bin\phpunit tests\Feature\Commands\ConfigCommandsTest.php --testdox
echo.

echo === Running Sniffing Commands Test ===
vendor\bin\phpunit tests\Feature\Commands\SniffingCommandsTest.php --testdox
echo.

echo === Running Frontend Tests ===
npm run test:coverage
echo.

echo All tests completed!
pause 