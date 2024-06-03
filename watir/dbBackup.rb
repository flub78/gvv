# Backup and restore a mysql database

##############################################################################
# Backup and Restore a MySql database
##############################################################################
class DbBackup
  
  # constructor
  def initialize(args={})
    @database = args[:database]
    @user = args[:user]
    @password = args[:password]
  end

  private 
  
  def check_params(user, password, database)
    if (user.empty?)
      raise "user not defined"
    end
    if (password.empty?)
      raise "password not defined"
    end
    if (database.empty?)
      raise "database not defined"
    end
  end

  public
  
  # --------------------------------------------------------------------------------
  # Backup a database
  # --------------------------------------------------------------------------------
  def backup(filename, user="", password="", database="")
    if (user.empty?)
      user = @user
    end
    if (password.empty?)
      password = @password
    end
    if (database.empty?)
      database = @database
    end
    check_params(user,password,database)

    system "mysqldump --user=#{user} --password=#{password} --default-character-set=utf8 #{database} > #{filename}"
  end

  # --------------------------------------------------------------------------------
  # Restore a database
  # --------------------------------------------------------------------------------
  def restore(filename, user="", password="", database="")
    if (user.empty?)
      user = @user
    end
    if (password.empty?)
      password = @password
    end
    if (database.empty?)
      database = @database
    end
    check_params(user,password,database)

    cmd = "mysql --user=#{user} --password=#{password} #{database} < #{filename}"
    # puts "cmd = " + cmd
    system cmd
  end

  # --------------------------------------------------------------------------------
  # Drop everything in a database
  # --------------------------------------------------------------------------------
  def drop(database = "", user="", password="")
    if (user.empty?)
      user = @user
    end
    if (password.empty?)
      password = @password
    end
    if (database.empty?)
      database = @database
    end
    check_params(user,password,database)

    cmd = "mysql --user=#{user} --password=#{password} -e 'drop database #{database};' #{database}"
    # puts "cmd = " + cmd
    system cmd
  end

  # --------------------------------------------------------------------------------
  # Create a database
  # --------------------------------------------------------------------------------
  def create(database, user="", password="")
    if (user.empty?)
      user = @user
    end
    if (password.empty?)
      password = @password
    end
    check_params(user,password,database)

    cmd = "mysql --user=#{user} --password=#{password} -e 'create database #{database};' "
    # puts "cmd = " + cmd
    system cmd
  end

end