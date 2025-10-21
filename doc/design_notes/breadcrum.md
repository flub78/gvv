# Breadcrumb


Short answer:
A breadcrumb (or breadcrumb navigation) is a UI element that shows the user’s location within a website or app hierarchy, typically displayed as a horizontal list of links separated by “>” or “/”.

They are supported by bootstrap


## Examples
'''
<div class="row mb-3">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="http://gvv.net/procedures">Procédures</a>
                        </li>
                        <li class="breadcrumb-item active">Avion</li>
                    </ol>
                </nav>
                <h2>
                    <i class="fas fa-book-open" aria-hidden="true"></i>
                    Avion                </h2>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group mt-4" role="group">
                                            <a href="http://gvv.net/procedures/edit/18" class="btn btn-primary">
                            <i class="fas fa-edit" aria-hidden="true"></i> Modifier
                        </a>
                        <a href="http://gvv.net/procedures/edit_markdown/18" class="btn btn-secondary">
                            <i class="fab fa-markdown" aria-hidden="true"></i> Éditer
                        </a>
                        <a href="http://gvv.net/procedures/attachments/18" class="btn btn-info">
                            <i class="fas fa-paperclip" aria-hidden="true"></i> Fichiers
                        </a>
                                    </div>
            </div>
        </div>
'''