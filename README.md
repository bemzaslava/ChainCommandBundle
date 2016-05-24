# ChainCommandBundle

Install:

Add bundle to AppKernel.php:
```
    public function registerBundles()
    {
        $bundles = [
            ...
            new ChainCommandBundle\ChainCommandBundle(),
        ];

        return $bundles;
    }
```


Usage:

Declare your commands as service with tag name `chaincommandbundle.command`:
```
services:

    foobundle.command.hello:
        class: FooBundle\Command\HelloCommand
        tags:
            - {name: 'chaincommandbundle.command', master: true, channel: 'my_chain'}

    barbundle.command.hi:
        class: BarBundle\Command\HiCommand
        tags:
            - {name: 'chaincommandbundle.command', channel: 'my_chain', weight: 20}
            
    # master: true - declare as start command for chain
    # channel: 'my_channel' - custom chain name
    # weight: 20 - weight for ordering command members (sort DESC)
```
