# Class to read and edit a PHP file considered as a configuration file
#
# The goal of this class is to edit these configuration files while preserving
# comments and formating. Formatting preservation is a best effort. The goal is
# to prevent the configuration editor to remove all comments and formatting, not
# to guarantee that all indentation is stricly preserve whatever it is.  
#
# These files contain:
#    * <?php open directive
#    * multiline comments started by /** and ended by */
#    * blank lines
#    * scalar string values:  $config['key'] = "string value";
#    * scalar string values:  $config['key'] = 'string value';
#    * scalar boolean values: $config['key'] = TRUE;
#    * scalar boolean values: $config['key'] = FALSE;
#    * scalar integer values: $config['key'] = 10;
#    * scalar decimal values: $config['key'] = 10.314;
#    * PHP statements: if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#
# Use cases
#	* load a configuration file
#   * get the list of keys
#   * get the value for a key, scalar or array
#   * change the value for a key, scalar or array
#   * save the configuration
#
# Design
#   Exact PHP grammar parser (no, too complex and too expensive)
#   or
#   search for configuration element definition outside of comments.
#
#   The second approach is not full proof. Eventually the parser could detect
#   the start of a configuration element where there is none, or miss some because
#   of a weird formating. 
#  
class PhpConfig

  ##############################################################################
  # Load a configuration from a file
  # Params:
  # +filename+:: name of the configuration file
  # +config_pattern+:: name of the configuration array
=begin
  @startuml
  
  title File Parser
  
  [*] --> Statements 
  Statements --> [*] :EOF
  
  Statements --> Comments : StartMultilineComment
  Comments --> Statements : EndMultilineComment
  Statements -left-> Statements : SingleLineComment
  Statements -left-> Statements : SingleLineConfigStatement
  Statements -left-> Statements : SingleLineStatement
  Statements --> MultilLineArrays : StartMultilineArray
  MultilLineArrays --> Statements : EndMultilineArray
  
  @enduml
=end
  ##############################################################################
  def initialize(filename, config_pattern = "config")
    @filename = filename
    @config_pattern = config_pattern
    @values = Hash.new
    @start_of = Hash.new
    @keys = []
    
    # Regular expressions used by the parser
    reg_comment_line1 = /^\s*\/\//
    reg_comment_line2 = /^\s*\#/
    reg_comment_line3 = /^\s*\/\*.*\*\//
    reg_comment_line = Regexp.union(reg_comment_line1, reg_comment_line2, reg_comment_line3)

    reg_comment_start = /^\s*\/\*/
    reg_comment_stop = /^.*\*\//
    
    reg_single_line_config = Regexp.new "^[[:blank:]]*\\\$#{@config_pattern}\\\[(.*)\\\][[:blank:]]*=[[:blank:]]*(.*);"
    
    cnt = 0
    state = :statements
    @lines = []
    File.readlines(filename).each do |line|
      
      @lines << line
      # Either I look for patterns and treat them depending on the state
      # Or I look for different patterns in every state
      
      case line
        when reg_comment_line
          # puts "#{cnt}: ### comment line #{line}"
          
        when reg_comment_start
          # puts "#{cnt}: ### start of comment #{line}"
          state = :comments
 
        when reg_comment_stop
          # puts "#{cnt}: ### end of comment #{line}"
          state = :statements
          
        when reg_single_line_config
          key = Regexp.last_match(1).tr('"', '').tr("'", '')
          value = Regexp.last_match(2)
          # puts "#{cnt}: CONFIG STATEMENT #{line}"
          # puts "last match = \"#{Regexp.last_match}\""
          # puts "key = \"#{key}\""
          # puts "value = \"#{value}\""
          @values[key] = value
          @start_of[cnt] = key
          @keys << key

      else
        
        if (state == :comments)
          # puts "#{cnt}: ### #{line}"
        else 
          # puts "#{cnt}: no match #{line}"          
        end   
      end
      
      cnt += 1
    end
  end
  
  ##############################################################################
  # Save the configuration
  # Params:
  # +filename+:: if present, name of the file to save. If not save in the file used to open
  ##############################################################################
  def save(filename = "")
    filename = @filename if (filename == "")
    begin
      file = File.open(filename, "w")
      cnt = 0
      @lines.each do |line|
        key = @start_of[cnt]
        if (key)
          str = "$#{@config_pattern}['#{key}'] = #{self.value(key)};\n"
          file.write(str)
        else
          file.write(line)
        end
        cnt += 1 
      end
      
    rescue IOError => e
      #some error occur, dir not writable etc.
    ensure
      file.close unless file.nil?
    end
  end
  
  ##############################################################################
  # Return the list of keys found in the configuration file
  ##############################################################################
  def keys
    return @keys
  end
  
  ##############################################################################
  # Get the value of a key
  # Params:
  # +key+:: 
  ##############################################################################
  def value(key, stripped=false)
    if (stripped)
      return @values[key].gsub!(/^\"|\"?$/, '')
    else
      return @values[key]
    end
  end
  
  ##############################################################################
  # Set the value of a key
  # Params:
  # +key+::
  # +value+:: scalar or array
  ##############################################################################
  def set(key, value)
    @values[key] = value
  end

  ##############################################################################
  # Return a line
  # Params:
  # +nb+:: line index
  ##############################################################################
  def line(nb)
    return @lines[nb]
  end
  
end