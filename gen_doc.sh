export GVV=$HOME/workspace/gvv2/application
export HTML=$HOME/doc/gvv
phpdoc -d $GVV/controllers,$GVV/helpers,$GVV/models,$GVV/libraries,$GVV/views -t $HTML 
