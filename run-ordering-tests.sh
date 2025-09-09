#!/bin/bash

# SavedFeast Ordering System Test Runner
# This script runs all tests related to the ordering and tracking system

echo "🧪 Running SavedFeast Ordering System Tests..."
echo "=============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test files to run
TEST_FILES=(
    "tests/Feature/OrderTest.php"
    "tests/Feature/OrderTrackingTest.php"
    "tests/Feature/ProviderOrderStatsTest.php"
    "tests/Feature/OrderRateLimitingTest.php"
    "tests/Feature/FrontendRateLimitingTest.php"
    "tests/Feature/CompleteOrderFlowTest.php"
    "tests/Feature/CountdownTimerTest.php"
    "tests/Feature/CashOnPickupOrderTest.php"
)

# Function to run a test file
run_test() {
    local test_file=$1
    local test_name=$(basename "$test_file" .php)
    
    echo -e "\n${BLUE}Running: $test_name${NC}"
    echo "----------------------------------------"
    
    if php artisan test "$test_file" --stop-on-failure; then
        echo -e "${GREEN}✅ $test_name - PASSED${NC}"
        return 0
    else
        echo -e "${RED}❌ $test_name - FAILED${NC}"
        return 1
    fi
}

# Function to run all tests
run_all_tests() {
    local failed_tests=()
    local passed_tests=()
    
    for test_file in "${TEST_FILES[@]}"; do
        if run_test "$test_file"; then
            passed_tests+=("$test_file")
        else
            failed_tests+=("$test_file")
        fi
    done
    
    echo -e "\n${BLUE}Test Summary${NC}"
    echo "============"
    echo -e "${GREEN}Passed: ${#passed_tests[@]}${NC}"
    echo -e "${RED}Failed: ${#failed_tests[@]}${NC}"
    
    if [ ${#failed_tests[@]} -gt 0 ]; then
        echo -e "\n${RED}Failed Tests:${NC}"
        for test in "${failed_tests[@]}"; do
            echo -e "  - $(basename "$test" .php)"
        done
        return 1
    else
        echo -e "\n${GREEN}🎉 All tests passed!${NC}"
        return 0
    fi
}

# Function to run specific test categories
run_category() {
    local category=$1
    
    case $category in
        "core")
            echo -e "${BLUE}Running Core Order Tests...${NC}"
            run_test "tests/Feature/OrderTest.php"
            run_test "tests/Feature/OrderTrackingTest.php"
            run_test "tests/Feature/CompleteOrderFlowTest.php"
            ;;
        "provider")
            echo -e "${BLUE}Running Provider Tests...${NC}"
            run_test "tests/Feature/ProviderOrderStatsTest.php"
            run_test "tests/Feature/OrderTrackingTest.php"
            ;;
        "rate-limiting")
            echo -e "${BLUE}Running Rate Limiting Tests...${NC}"
            run_test "tests/Feature/OrderRateLimitingTest.php"
            run_test "tests/Feature/FrontendRateLimitingTest.php"
            ;;
        "countdown")
            echo -e "${BLUE}Running Countdown Timer Tests...${NC}"
            run_test "tests/Feature/CountdownTimerTest.php"
            ;;
        "payment")
            echo -e "${BLUE}Running Payment Tests...${NC}"
            run_test "tests/Feature/CashOnPickupOrderTest.php"
            ;;
        *)
            echo -e "${RED}Unknown category: $category${NC}"
            echo "Available categories: core, provider, rate-limiting, countdown, payment"
            return 1
            ;;
    esac
}

# Main script logic
case "${1:-all}" in
    "all")
        run_all_tests
        ;;
    "core"|"provider"|"rate-limiting"|"countdown"|"payment")
        run_category "$1"
        ;;
    "help"|"-h"|"--help")
        echo "SavedFeast Ordering System Test Runner"
        echo ""
        echo "Usage: $0 [category]"
        echo ""
        echo "Categories:"
        echo "  all           - Run all ordering system tests (default)"
        echo "  core          - Run core order functionality tests"
        echo "  provider      - Run provider-specific tests"
        echo "  rate-limiting - Run rate limiting tests"
        echo "  countdown     - Run countdown timer tests"
        echo "  payment       - Run payment method tests"
        echo "  help          - Show this help message"
        echo ""
        echo "Examples:"
        echo "  $0                    # Run all tests"
        echo "  $0 core              # Run core tests only"
        echo "  $0 rate-limiting     # Run rate limiting tests only"
        ;;
    *)
        echo -e "${RED}Unknown option: $1${NC}"
        echo "Use '$0 help' for usage information"
        exit 1
        ;;
esac
