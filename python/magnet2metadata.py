import sys, tempfile, shutil, json, time
import libtorrent as lt

if __name__ == "__main__":
    magnet_uri = sys.argv[1]
    ses = lt.session()
    ses.listen_on(6881, 6891)
    ses.start_dht()
    ses.add_dht_router('router.bittorrent.com', 6881)
    ses.add_dht_router('router.bittorrent.com', 6881)

    tempdir = tempfile.mkdtemp()
    params = {
    			"save_path" : tempdir,
                "duplicate_is_error" : True,
                "paused" : False,
                "auto_managed" : True,
                "url" : magnet_uri,
                "storage_mode" : lt.storage_mode_t(2)

    }

    handle = ses.add_torrent(params)

    count = 0
    while not handle.has_metadata():
        try:
            if count >= 10:
                print json.dumps({'error': True, 'message': "Getting the magnet link's metadata is taking too long!"})
                ses.remove_torrent(handle)
                shutil.rmtree(tempdir)
                sys.exit(0)
            
            time.sleep(1)
            count += 1

        except NameError as e:
            print json.dumps({'error': True, 'message': e})
            ses.remove_torrent(handle)
            shutil.rmtree(tempdir)
            sys.exit(0)


    torrent_info = handle.get_torrent_info()
    print json.dumps({'name': torrent_info.name(), 'hashString': str(torrent_info.info_hash()), 'totalSize': sum([f.size for f in torrent_info.files()])})
    ses.remove_torrent(handle)
    shutil.rmtree(tempdir)
    sys.exit(0)