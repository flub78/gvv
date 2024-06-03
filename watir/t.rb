require 'watir'

browser = Watir::Browser.new :firefox

browser.goto 'watir.com'
browser.link(text: 'Guides').click

puts browser.title
# => 'Guides – Watir Project'
browser.close
