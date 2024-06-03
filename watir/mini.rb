# small test to check that minitest gem is installed
require "minitest/autorun"

class TestMe < Minitest::Test
  def setup
    puts "minitest.setup"
  end

  def test_that_kitty_can_eat
    assert_equal "true", "true", "true == true"
  end

  def test_that_will_be_skipped
    skip "test this later"
  end

  def teardown
    puts "minitest.teardown"
  end

end
