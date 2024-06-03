require 'watir'
require 'headless'

headless = Headless.new
headless.start
browser = Watir::Browser.new :firefox

browser.goto 'watir.com'
browser.link(text: 'Guides').click

puts browser.title
# => 'Guides – Watir Project'
browser.close
headless.destroy
