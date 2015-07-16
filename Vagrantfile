# Use project.ini for basic VM configuration.

config_path = './project.ini'

if !File.exist?(config_path)
  raise 'Configuration file not found! Please copy example.project.ini to project.ini and try again.'
end

def parse_ini(path)
  config = {}
  category = ''

  File.foreach(path) do |line|
    line.strip

    # Skip comments and whitespace
    if (line[0] != ?# and line[0] != ?; and line =~ /\s/)
      i = line.index(']')
      if (i)
        category = line[1..i - 1].strip
        config[ category ] = {}
      else
        j = line.index('=')
        if (j)
          config[ category ][ line[0..j - 1].strip ] = line[j + 1..-1].strip
        else
          config[ category ][ line ] = ''
        end
      end
    end
  end

  return config
end

config = parse_ini(config_path)

# Backward compatibility: set default value for memory
unless config['vagrant'].has_key?('memory')
  config['vagrant']['memory'] = '1024'
end

print "=======================================\n"
print "  Vagrant IP: " + config['vagrant']['ip'] + "\n"
print "  Vagrant forward port: " + config['vagrant']['forwarded_port'] + "\n"
print "  Vagrant hostname: " + config['vagrant']['hostname'] + "\n"
print "  Vagrant memory: " + config['vagrant']['memory'] + "\n"
print "=======================================\n"

VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |node_config|

  node_config.ssh.shell = "bash -c 'BASH_ENV=/etc/profile exec bash'"

  node_config.vm.box = "ubuntu/monkiitrusty64"
  node_config.vm.box_url = "http://my.monkii.com.au/vagrant/boxes/trusty-server-amd-64-monkii-vagrant.box"
  # node_config.vm.hostname = config['vagrant'][hostname]

  node_config.vm.provision :shell, path: "setup/bootstrap.sh"

  node_config.vm.network :private_network, ip: config['vagrant']['ip']
  node_config.vm.network :forwarded_port, guest: 80, host: config['vagrant']['forwarded_port'].to_i
  node_config.vm.network :forwarded_port, guest: 8585, host: 8585

  node_config.vm.provider :virtualbox do |vb|
    vb.customize [
      "modifyvm", :id,
      "--name", config['vagrant']['hostname'],
      "--natdnshostresolver1", "on",
      "--memory", config['vagrant']['memory']
    ]
  end

  # Share project folder (where Vagrantfile is located) as /vagrant
  node_config.vm.synced_folder ".", "/vagrant", nfs: true

end
